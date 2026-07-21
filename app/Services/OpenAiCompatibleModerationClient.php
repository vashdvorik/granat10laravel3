<?php

namespace App\Services;

use App\Contracts\AiModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Exceptions\AiModerationProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;

class OpenAiCompatibleModerationClient implements AiModerationClient
{
    public function __construct(
        private readonly GeminiRequestThrottle $requestThrottle,
        private readonly ProductModerationResultValidator $resultValidator,
        private readonly ProductModerationImagePreparer $imagePreparer,
        private readonly AiProviderUrlValidator $urlValidator,
        private readonly ProductModerationPrompt $prompt,
    ) {}

    public function moderate(ProductModerationPayload $payload): ProductModerationResult
    {
        $configuration = $this->configurationKey();
        $model = (string) config("moderation.{$configuration}.model");

        if ($model === '') {
            throw new AiModerationProviderException('AI model is not configured.');
        }

        $baseUrl = rtrim((string) config("moderation.{$configuration}.base_url"), '/');

        try {
            $this->urlValidator->validate($baseUrl);
        } catch (InvalidArgumentException $exception) {
            throw new AiModerationProviderException(
                'AI API URL configuration is unsafe.',
                retryable: false,
                previous: $exception,
            );
        }

        $path = '/'.ltrim((string) config("moderation.{$configuration}.chat_completions_path"), '/');
        $images = $this->supportsVision()
            ? $this->imagePreparer->prepare($payload->imagePaths)
            : [];
        $response = $this->send($baseUrl.$path, $payload, $images);

        if (! $response->successful()) {
            throw new AiModerationProviderException(
                $this->errorMessage($response),
                retryable: $this->isRetryableStatus($response->status())
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiModerationProviderException('AI API returned an empty response.');
        }

        try {
            $result = json_decode($this->stripCodeFence($content), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiModerationProviderException(
                'AI API returned invalid JSON.',
                retryable: true,
                previous: $exception
            );
        }

        if (! is_array($result)) {
            throw new AiModerationProviderException('AI API response has an invalid structure.');
        }

        return $this->resultValidator->validate(
            result: $result,
            providerRequestId: $response->header('x-request-id') ?: $response->json('id'),
            imagesAnalyzed: count($images) === count($payload->imagePaths),
        );
    }

    private function send(string $url, ProductModerationPayload $payload, array $images): Response
    {
        $attempts = max(0, (int) config('moderation.http.retry_times')) + 1;
        $lastException = null;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $configuration = $this->configurationKey();
            $this->requestThrottle->forScope('text:'.$configuration)->wait();

            try {
                $request = Http::acceptJson()->asJson()->timeout((int) config('moderation.timeout', 60));
                $apiKey = (string) config("moderation.{$configuration}.api_key");

                if ($apiKey !== '') {
                    $request = $request->withToken($apiKey);
                }

                $response = $request->post($url, $this->requestBody($payload, $images));
            } catch (ConnectionException $exception) {
                $lastException = $exception;

                if ($attempt < $attempts) {
                    continue;
                }

                break;
            }

            if ($response->successful() || ! $this->isRetryableStatus($response->status())) {
                return $response;
            }

            if ($attempt < $attempts) {
                usleep(max(0, (int) config('moderation.http.retry_sleep_ms', 500)) * 1000);
            }
        }

        if ($lastException !== null) {
            throw new AiModerationProviderException(
                'AI API connection failed.',
                retryable: true,
                previous: $lastException
            );
        }

        return $response;
    }

    private function configurationKey(): string
    {
        return config('moderation.provider') === 'deepseek' ? 'deepseek' : 'openai_compatible';
    }

    private function requestBody(ProductModerationPayload $payload, array $images): array
    {
        $productData = json_encode([
            'product_id' => $payload->productId,
            'name' => $payload->name,
            'description' => $payload->description,
            'features' => $payload->features,
            'category' => $payload->categoryName,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($images === []) {
            $content = $productData;
        } else {
            $content = [[
                'type' => 'text',
                'text' => $productData,
            ]];
        }

        foreach ($images as $image) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$image->mimeType};base64,{$image->base64Data}",
                    'detail' => 'low',
                ],
            ];
        }

        return [
            'model' => (string) config('moderation.openai_compatible.model'),
            'temperature' => 0,
            'max_tokens' => (int) config('moderation.http.max_output_tokens', 1200),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->prompt->instruction().' '.$this->responseContract()],
                ['role' => 'user', 'content' => $content],
            ],
        ];
    }

    private function errorMessage(Response $response): string
    {
        $body = trim($response->body());

        if ($body === '') {
            return 'AI API returned HTTP '.$response->status().'.';
        }

        return 'AI API returned HTTP '.$response->status().': '
            .mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 500);
    }

    private function supportsVision(): bool
    {
        return (bool) config('moderation.vision.enabled', true)
            && (bool) config('moderation.openai_compatible.vision_enabled', false)
            && ! config('moderation.images.enabled', false);
    }

    private function responseContract(): string
    {
        return 'Верни только JSON. '
            .'Разрешённые code в violations: '.implode(', ', $this->allowedViolationCodes()).'. '
            .'Не возвращай другие code. Все тексты для продавца пиши только на русском языке: evidence, suggestion и seller_message. '
            .'Формат: {"decision":"allow|review|block","risk_score":0..1,'
            .'"violations":[{"code":"string","confidence":0..1,"evidence":"string",'
            .'"field":"name|description|features|category|images|general","suggestion":"string|null"}],'
            .'"seller_message":"string|null"}.';
    }

    /**
     * @return array<int, string>
     */
    private function allowedViolationCodes(): array
    {
        return [
            'spam',
            'nonsense',
            'prohibited_goods',
            'prohibited_services',
            'illegal_goods',
            'illegal_services',
            'illegal_drugs',
            'weapons',
            'alcohol',
            'adult_services',
            'fraud',
            'fraud_financial_scheme',
            'documents_money',
            'profanity',
            'obscene_language',
            'offensive_language',
            'wrong_category',
            'category_mismatch',
            'prohibited_image',
            'unsafe_image',
        ];
    }

    private function stripCodeFence(string $content): string
    {
        $content = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }

    private function isRetryableStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }
}


<?php

namespace App\Services;

use App\Contracts\ProductImageModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Exceptions\AiModerationProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class OpenAiModerationImageClient implements ProductImageModerationClient
{
    public function __construct(
        private readonly GeminiRequestThrottle $requestThrottle,
        private readonly ProductModerationImagePreparer $imagePreparer,
        private readonly AiProviderUrlValidator $urlValidator,
    ) {}

    public function moderate(ProductModerationPayload $payload): ProductModerationResult
    {
        $apiKey = (string) config('moderation.images.openai_moderation.api_key');

        if ($apiKey === '') {
            throw new AiModerationProviderException('OpenAI image moderation API key is not configured.', retryable: false);
        }

        $baseUrl = rtrim((string) config('moderation.images.openai_moderation.base_url'), '/');

        try {
            $this->urlValidator->validate($baseUrl);
        } catch (InvalidArgumentException $exception) {
            throw new AiModerationProviderException(
                'OpenAI image moderation API URL configuration is unsafe.',
                retryable: false,
                previous: $exception,
            );
        }

        $images = $this->imagePreparer->prepare($payload->imagePaths);

        if ($images === []) {
            return new ProductModerationResult(
                decision: 'review',
                riskScore: 0.5,
                violations: [[
                    'code' => 'empty_image',
                    'confidence' => 1.0,
                    'evidence' => 'Изображения товара не удалось подготовить для проверки.',
                    'field' => 'images',
                    'suggestion' => 'Загрузите корректное изображение товара.',
                ]],
                sellerMessage: 'Загрузите корректное изображение товара.',
                imagesAnalyzed: false,
            );
        }

        $violations = [];
        $riskScore = 0.0;
        $requestIds = [];

        foreach ($images as $index => $image) {
            $response = $this->send($baseUrl.'/moderations', $apiKey, [$image]);

            if (! $response->successful()) {
                throw new AiModerationProviderException(
                    $this->errorMessage($response),
                    retryable: $this->isRetryableStatus($response->status())
                );
            }

            $result = $response->json('results.0');

            if (! is_array($result) || ! is_bool($result['flagged'] ?? null)) {
                throw new AiModerationProviderException('OpenAI image moderation API returned an invalid response.', retryable: true);
            }

            $imageResult = $this->toModerationResult(
                result: $result,
                providerRequestId: null,
                imagesAnalyzed: true,
            );
            $riskScore = max($riskScore, $imageResult->riskScore);
            foreach ($imageResult->violations as $violation) {
                $violations[] = $violation + ['image_index' => $index + 1];
            }
            $requestId = $response->header('x-request-id') ?: $response->json('id');
            if (is_string($requestId) && $requestId !== '') {
                $requestIds[] = $requestId;
            }
        }

        return new ProductModerationResult(
            decision: $violations === [] ? 'allow' : 'review',
            riskScore: $riskScore,
            violations: $violations,
            sellerMessage: $violations === [] ? null : 'Изображения товара требуют дополнительной проверки.',
            providerRequestId: $requestIds === [] ? null : implode(',', $requestIds),
            imagesAnalyzed: count($images) === count($payload->imagePaths),
        );
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function send(
        string $url,
        string $apiKey,
        array $images,
    ): Response {
        $attempts = max(0, (int) config('moderation.http.retry_times')) + 1;
        $lastException = null;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->requestThrottle->forScope('image:openai_moderation')->wait();

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout((int) config('moderation.images.timeout', 60))
                    ->withToken($apiKey)
                    ->post($url, [
                        'model' => (string) config('moderation.images.openai_moderation.model'),
                        'input' => $this->input($images),
                    ]);
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
                'OpenAI image moderation API connection failed.',
                retryable: true,
                previous: $lastException,
            );
        }

        return $response;
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     * @return array<int, array<string, mixed>>
     */
    private function input(array $images): array
    {
        $input = [[
            'type' => 'text',
            'text' => 'Moderate the attached images for safety.',
        ]];

        foreach ($images as $image) {
            $input[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$image->mimeType};base64,{$image->base64Data}",
                ],
            ];
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function toModerationResult(
        array $result,
        ?string $providerRequestId,
        bool $imagesAnalyzed,
    ): ProductModerationResult {
        $categories = is_array($result['categories'] ?? null) ? $result['categories'] : [];
        $scores = is_array($result['category_scores'] ?? null) ? $result['category_scores'] : [];
        $flaggedCategories = array_keys(array_filter($categories, fn (mixed $flagged): bool => $flagged === true));
        $riskScore = $this->riskScore($scores, (bool) $result['flagged']);
        $violations = [];

        foreach ($flaggedCategories as $category) {
            $violations[] = [
                'code' => $this->violationCode($category),
                'confidence' => $this->score($scores[$category] ?? $riskScore),
                'evidence' => "OpenAI Moderation flagged image category: {$category}.",
                'field' => 'images',
                'suggestion' => 'Проверьте изображения товара перед публикацией.',
            ];
        }

        if ((bool) $result['flagged'] && $violations === []) {
            $violations[] = [
                'code' => 'unsafe_image',
                'confidence' => $riskScore,
                'evidence' => 'OpenAI Moderation flagged product images for manual review.',
                'field' => 'images',
                'suggestion' => 'Проверьте изображения товара перед публикацией.',
            ];
        }

        return new ProductModerationResult(
            decision: $violations === [] ? 'allow' : 'review',
            riskScore: $riskScore,
            violations: $violations,
            sellerMessage: $violations === [] ? null : 'Изображения товара требуют дополнительной проверки.',
            providerRequestId: $providerRequestId,
            imagesAnalyzed: $imagesAnalyzed,
        );
    }

    /**
     * @param  array<string, mixed>  $scores
     */
    private function riskScore(array $scores, bool $flagged): float
    {
        $numericScores = array_map(fn (mixed $score): float => $this->score($score), $scores);

        return $numericScores === [] ? ($flagged ? 1.0 : 0.0) : max($numericScores);
    }

    private function score(mixed $score): float
    {
        if (! is_int($score) && ! is_float($score)) {
            return 0.0;
        }

        return max(0.0, min(1.0, (float) $score));
    }

    private function violationCode(string $category): string
    {
        return match ($category) {
            'sexual', 'sexual/minors' => 'adult_services',
            'harassment', 'harassment/threatening', 'hate', 'hate/threatening' => 'offensive_language',
            'illicit' => 'illegal_services',
            'illicit/violent' => 'weapons',
            'self-harm', 'self-harm/intent', 'self-harm/instructions' => 'unsafe_image',
            'violence', 'violence/graphic' => 'unsafe_image',
            default => 'unsafe_image',
        };
    }

    private function errorMessage(Response $response): string
    {
        $body = trim($response->body());

        if ($body === '') {
            return 'OpenAI image moderation API returned HTTP '.$response->status().'.';
        }

        return 'OpenAI image moderation API returned HTTP '.$response->status().': '
            .mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 500);
    }

    private function isRetryableStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }
}

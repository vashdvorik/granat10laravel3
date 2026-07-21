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
use JsonException;

class UniversalImageModerationClient implements ProductImageModerationClient
{
    public function __construct(
        private readonly GeminiRequestThrottle $requestThrottle,
        private readonly ProductModerationResultValidator $resultValidator,
        private readonly ProductModerationImagePreparer $imagePreparer,
        private readonly AiProviderUrlValidator $urlValidator,
        private readonly ProductImageModerationPrompt $prompt,
    ) {}

    public function moderate(ProductModerationPayload $payload): ProductModerationResult
    {
        $provider = $this->provider();
        $apiKey = (string) config('moderation.images.api_key');

        if ($apiKey === '') {
            throw new AiModerationProviderException('Image moderation API key is not configured.');
        }

        $model = (string) config('moderation.images.model');
        $baseUrl = rtrim((string) config('moderation.images.base_url'), '/');
        $endpointPath = $this->endpointPath($provider, $model);

        try {
            $this->urlValidator->validate($baseUrl);
        } catch (InvalidArgumentException $exception) {
            throw new AiModerationProviderException(
                'Image moderation API URL configuration is unsafe.',
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
                    'evidence' => 'РР·РѕР±СЂР°Р¶РµРЅРёСЏ С‚РѕРІР°СЂР° РЅРµ СѓРґР°Р»РѕСЃСЊ РїРѕРґРіРѕС‚РѕРІРёС‚СЊ РґР»СЏ РїСЂРѕРІРµСЂРєРё.',
                    'field' => 'images',
                    'suggestion' => 'Р—Р°РіСЂСѓР·РёС‚Рµ РєРѕСЂСЂРµРєС‚РЅРѕРµ РёР·РѕР±СЂР°Р¶РµРЅРёРµ С‚РѕРІР°СЂР°.',
                ]],
                sellerMessage: 'Р—Р°РіСЂСѓР·РёС‚Рµ РєРѕСЂСЂРµРєС‚РЅРѕРµ РёР·РѕР±СЂР°Р¶РµРЅРёРµ С‚РѕРІР°СЂР°.',
                imagesAnalyzed: false,
            );
        }

        $response = $this->send(
            provider: $provider,
            url: $baseUrl.$endpointPath,
            apiKey: $apiKey,
            payload: $payload,
            images: $images,
        );

        if (! $response->successful()) {
            throw new AiModerationProviderException(
                $this->errorMessage($response),
                retryable: $this->isRetryableStatus($response->status())
            );
        }

        return $this->parseResponse($provider, $response, $payload, $images);
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function send(
        string $provider,
        string $url,
        string $apiKey,
        ProductModerationPayload $payload,
        array $images,
    ): Response {
        try {
            $this->requestThrottle->forScope('image:'.$provider)->wait();

            return $this->request($provider, $apiKey)
                ->timeout((int) config('moderation.images.timeout', 60))
                ->post($url, $this->requestBody($provider, $payload, $images));
        } catch (ConnectionException $exception) {
            throw new AiModerationProviderException(
                'Image moderation API connection failed.',
                retryable: true,
                previous: $exception
            );
        }
    }

    private function provider(): string
    {
        $provider = (string) config('moderation.images.provider', 'gemini');

        return match ($provider) {
            'google' => 'gemini',
            'openai' => 'openai_compatible',
            default => $provider,
        };
    }

    private function endpointPath(string $provider, string $model): string
    {
        $path = (string) config('moderation.images.endpoint_path', $this->defaultEndpointPath($provider));
        $path = str_replace('{model}', $model, $path);

        return '/'.ltrim($path, '/');
    }

    private function defaultEndpointPath(string $provider): string
    {
        return match ($provider) {
            'gemini' => '/models/{model}:generateContent',
            'openai_moderation' => '/moderations',
            default => '/chat/completions',
        };
    }

    private function request(string $provider, string $apiKey)
    {
        $authHeader = (string) config('moderation.images.auth_header', $this->defaultAuthHeader($provider));
        $authPrefix = (string) config('moderation.images.auth_prefix', $this->defaultAuthPrefix($provider));
        $headers = $authHeader === 'x-goog-api-key'
            ? [$authHeader => $apiKey]
            : [$authHeader => trim($authPrefix) === '' ? $apiKey : rtrim($authPrefix).' '.$apiKey];

        return Http::acceptJson()
            ->asJson()
            ->withHeaders($headers);
    }

    private function defaultAuthHeader(string $provider): string
    {
        return match ($provider) {
            'gemini' => 'x-goog-api-key',
            default => 'Authorization',
        };
    }

    private function defaultAuthPrefix(string $provider): string
    {
        return match ($provider) {
            'gemini' => '',
            default => 'Bearer',
        };
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function requestBody(string $provider, ProductModerationPayload $payload, array $images): array
    {
        return match ($provider) {
            'openai_moderation' => $this->openAiModerationBody($images),
            'gemini' => $this->geminiBody($payload, $images),
            default => $this->openAiCompatibleBody($payload, $images),
        };
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function geminiBody(ProductModerationPayload $payload, array $images): array
    {
        $parts = [[
            'text' => json_encode([
                'product_id' => $payload->productId,
                'name' => $payload->name,
                'description' => $payload->description,
                'features' => $payload->features,
                'category' => $payload->categoryName,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]];

        foreach ($images as $image) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $image->mimeType,
                    'data' => $image->base64Data,
                ],
            ];
        }

        return [
            'systemInstruction' => [
                'parts' => [[
                    'text' => $this->prompt->instruction(),
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => (int) config('moderation.http.max_output_tokens', 1200),
                'responseMimeType' => 'application/json',
            ],
        ];
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function openAiCompatibleBody(ProductModerationPayload $payload, array $images): array
    {
        $productData = json_encode([
            'product_id' => $payload->productId,
            'name' => $payload->name,
            'description' => $payload->description,
            'features' => $payload->features,
            'category' => $payload->categoryName,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $content = [[
            'type' => 'text',
            'text' => $productData,
        ]];

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
            'model' => (string) config('moderation.images.model'),
            'temperature' => 0,
            'max_tokens' => (int) config('moderation.http.max_output_tokens', 1200),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->prompt->instruction().' '.$this->responseContract()],
                ['role' => 'user', 'content' => $content],
            ],
        ];
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function openAiModerationBody(array $images): array
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

        return [
            'model' => (string) config('moderation.images.model'),
            'input' => $input,
        ];
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function parseResponse(
        string $provider,
        Response $response,
        ProductModerationPayload $payload,
        array $images,
    ): ProductModerationResult {
        return match ($provider) {
            'openai_moderation' => $this->parseOpenAiModerationResponse($response, $payload, $images),
            'gemini' => $this->parseGeminiResponse($response, $payload, $images),
            default => $this->parseOpenAiCompatibleResponse($response, $payload, $images),
        };
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function parseGeminiResponse(Response $response, ProductModerationPayload $payload, array $images): ProductModerationResult
    {
        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new AiModerationProviderException('Image moderation API returned an empty response.');
        }

        try {
            $result = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiModerationProviderException(
                'Image moderation API returned invalid JSON.',
                retryable: true,
                previous: $exception
            );
        }

        if (! is_array($result)) {
            throw new AiModerationProviderException('Image moderation API response has an invalid structure.');
        }

        return $this->resultValidator->validate(
            result: $result,
            providerRequestId: filled($response->header('x-request-id'))
                ? $response->header('x-request-id')
                : $response->json('responseId'),
            imagesAnalyzed: count($images) === count($payload->imagePaths),
        );
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function parseOpenAiCompatibleResponse(Response $response, ProductModerationPayload $payload, array $images): ProductModerationResult
    {
        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiModerationProviderException('Image moderation API returned an empty response.');
        }

        try {
            $result = json_decode($this->stripCodeFence($content), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiModerationProviderException(
                'Image moderation API returned invalid JSON.',
                retryable: true,
                previous: $exception
            );
        }

        if (! is_array($result)) {
            throw new AiModerationProviderException('Image moderation API response has an invalid structure.');
        }

        return $this->resultValidator->validate(
            result: $result,
            providerRequestId: $response->header('x-request-id') ?: $response->json('id'),
            imagesAnalyzed: count($images) === count($payload->imagePaths),
        );
    }

    /**
     * @param  array<int, \App\Data\ModerationImage>  $images
     */
    private function parseOpenAiModerationResponse(Response $response, ProductModerationPayload $payload, array $images): ProductModerationResult
    {
        $result = $response->json('results.0');

        if (! is_array($result) || ! is_bool($result['flagged'] ?? null)) {
            throw new AiModerationProviderException('OpenAI image moderation API returned an invalid response.', retryable: true);
        }

        $imageResult = $this->toModerationResult(
            result: $result,
            providerRequestId: null,
            imagesAnalyzed: true,
        );
        $requestId = $response->header('x-request-id') ?: $response->json('id');

        return new ProductModerationResult(
            decision: $imageResult->decision,
            riskScore: $imageResult->riskScore,
            violations: $imageResult->violations,
            sellerMessage: $imageResult->sellerMessage,
            providerRequestId: is_string($requestId) && $requestId !== '' ? $requestId : null,
            imagesAnalyzed: count($images) === count($payload->imagePaths),
        );
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
                'suggestion' => 'РџСЂРѕРІРµСЂСЊС‚Рµ РёР·РѕР±СЂР°Р¶РµРЅРёСЏ С‚РѕРІР°СЂР° РїРµСЂРµРґ РїСѓР±Р»РёРєР°С†РёРµР№.',
            ];
        }

        if ((bool) $result['flagged'] && $violations === []) {
            $violations[] = [
                'code' => 'unsafe_image',
                'confidence' => $riskScore,
                'evidence' => 'OpenAI Moderation flagged product images for manual review.',
                'field' => 'images',
                'suggestion' => 'РџСЂРѕРІРµСЂСЊС‚Рµ РёР·РѕР±СЂР°Р¶РµРЅРёСЏ С‚РѕРІР°СЂР° РїРµСЂРµРґ РїСѓР±Р»РёРєР°С†РёРµР№.',
            ];
        }

        return new ProductModerationResult(
            decision: $violations === [] ? 'allow' : 'review',
            riskScore: $riskScore,
            violations: $violations,
            sellerMessage: $violations === [] ? null : 'РР·РѕР±СЂР°Р¶РµРЅРёСЏ С‚РѕРІР°СЂР° С‚СЂРµР±СѓСЋС‚ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕР№ РїСЂРѕРІРµСЂРєРё.',
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
            return 'Image moderation API returned HTTP '.$response->status().'.';
        }

        return 'Image moderation API returned HTTP '.$response->status().': '
            .mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 500);
    }

    private function isRetryableStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    private function stripCodeFence(string $content): string
    {
        $content = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }

    private function responseContract(): string
    {
        return 'Верни только JSON. Разрешённые code в violations: '.implode(', ', $this->allowedViolationCodes()).'. '
            .'Все тексты для продавца пиши только на русском языке: evidence, suggestion и seller_message. '
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
}


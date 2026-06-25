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

class GoogleGeminiImageModerationClient implements ProductImageModerationClient
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
        $apiKey = (string) config('moderation.images.google.api_key');

        if ($apiKey === '') {
            throw new AiModerationProviderException('Image moderation API key is not configured.');
        }

        $model = (string) config('moderation.images.google.model');
        $baseUrl = rtrim((string) config('moderation.images.google.base_url'), '/');

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
                    'evidence' => 'Изображения товара не удалось подготовить для проверки.',
                    'field' => 'images',
                    'suggestion' => 'Загрузите корректное изображение товара.',
                ]],
                sellerMessage: 'Загрузите корректное изображение товара.',
                imagesAnalyzed: false,
            );
        }

        $response = $this->send("{$baseUrl}/models/{$model}:generateContent", $apiKey, $payload, $images);

        if (! $response->successful()) {
            throw new AiModerationProviderException(
                $this->errorMessage($response),
                retryable: $response->status() === 429 || $response->status() >= 500
            );
        }

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

    private function send(
        string $url,
        string $apiKey,
        ProductModerationPayload $payload,
        array $images,
    ): Response {
        try {
            $this->requestThrottle->wait();

            return Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('moderation.images.timeout', 60))
                ->post($url, $this->requestBody($payload, $images));
        } catch (ConnectionException $exception) {
            throw new AiModerationProviderException(
                'Image moderation API connection failed.',
                retryable: true,
                previous: $exception
            );
        }
    }

    private function requestBody(ProductModerationPayload $payload, array $images): array
    {
        $parts = [[
            'text' => json_encode([
                'product_id' => $payload->productId,
                'name' => $payload->name,
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

    private function errorMessage(Response $response): string
    {
        $body = trim($response->body());

        if ($body === '') {
            return 'Image moderation API returned HTTP '.$response->status().'.';
        }

        return 'Image moderation API returned HTTP '.$response->status().': '
            .mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 500);
    }
}

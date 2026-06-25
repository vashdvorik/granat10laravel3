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

class GoogleGeminiModerationClient implements AiModerationClient
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
        $apiKey = (string) config('moderation.google.api_key');

        if ($apiKey === '') {
            throw new AiModerationProviderException('Gemini API key is not configured.');
        }

        $model = (string) config('moderation.google.model');
        $baseUrl = rtrim((string) config('moderation.google.base_url'), '/');

        try {
            $this->urlValidator->validate($baseUrl);
        } catch (InvalidArgumentException $exception) {
            throw new AiModerationProviderException(
                'Gemini API URL configuration is unsafe.',
                retryable: false,
                previous: $exception,
            );
        }

        $url = "{$baseUrl}/models/{$model}:generateContent";

        $images = $this->imagePreparer->prepare($payload->imagePaths);
        $response = $this->send($url, $apiKey, $payload, $images);

        if (! $response->successful()) {
            throw new AiModerationProviderException(
                'Gemini API returned HTTP '.$response->status().'.',
                retryable: $this->isRetryableStatus($response->status())
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new AiModerationProviderException('Gemini API returned an empty response.');
        }

        try {
            $result = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiModerationProviderException(
                'Gemini API returned invalid JSON.',
                retryable: true,
                previous: $exception
            );
        }

        if (! is_array($result)) {
            throw new AiModerationProviderException('Gemini API response has an invalid structure.');
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
        $retries = max(0, (int) (
            config('moderation.google.retry_times')
            ?? config('moderation.http.retry_times', 2)
        ));
        $attempts = $retries + 1;
        $lastConnectionException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->requestThrottle->wait();

            try {
                $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                    ->acceptJson()
                    ->asJson()
                    ->timeout((int) config('moderation.timeout', 60))
                    ->post($url, $this->requestBody($payload, $images));
            } catch (ConnectionException $exception) {
                $lastConnectionException = $exception;

                if ($attempt < $attempts) {
                    continue;
                }

                break;
            }

            if ($response->successful() || ! $this->isRetryableStatus($response->status())) {
                return $response;
            }

            if ($attempt < $attempts) {
                usleep(max(0, (int) (
                    config('moderation.google.retry_sleep_ms')
                    ?? config('moderation.http.retry_sleep_ms', 500)
                )) * 1000);
            }
        }

        if ($lastConnectionException !== null) {
            throw new AiModerationProviderException(
                'Gemini API connection failed.',
                retryable: true,
                previous: $lastConnectionException
            );
        }

        return $response;
    }

    private function isRetryableStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestBody(ProductModerationPayload $payload, array $images): array
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
                'maxOutputTokens' => (int) (
                    config('moderation.google.max_output_tokens')
                    ?? config('moderation.http.max_output_tokens', 1200)
                ),
                'responseMimeType' => 'application/json',
                'responseJsonSchema' => $this->responseSchema(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['decision', 'risk_score', 'violations', 'seller_message'],
            'properties' => [
                'decision' => [
                    'type' => 'string',
                    'enum' => ['allow', 'review', 'block'],
                ],
                'risk_score' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'violations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['code', 'confidence', 'evidence', 'field', 'suggestion'],
                        'properties' => [
                            'code' => [
                                'type' => 'string',
                                'enum' => $this->allowedViolationCodes(),
                            ],
                            'confidence' => [
                                'type' => 'number',
                                'minimum' => 0,
                                'maximum' => 1,
                            ],
                            'evidence' => ['type' => 'string'],
                            'field' => [
                                'type' => 'string',
                                'enum' => ['name', 'description', 'features', 'category', 'images', 'general'],
                            ],
                            'suggestion' => ['type' => ['string', 'null']],
                        ],
                    ],
                ],
                'seller_message' => [
                    'type' => ['string', 'null'],
                ],
            ],
        ];
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

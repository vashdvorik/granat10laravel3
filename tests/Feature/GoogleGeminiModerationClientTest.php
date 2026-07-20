<?php

namespace Tests\Feature;

use App\Data\ProductModerationPayload;
use App\Exceptions\AiModerationProviderException;
use App\Services\GeminiRequestThrottle;
use App\Services\GoogleGeminiModerationClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleGeminiModerationClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moderation.google.requests_per_minute', 0);
        config()->set('moderation.google.api_key', 'test-secret-key');
        config()->set('moderation.google.model', 'gemini-test-model');
        config()->set('moderation.google.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        config()->set('moderation.google.retry_times', 1);
        config()->set('moderation.google.retry_sleep_ms', 0);
    }

    public function test_client_parses_structured_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'responseId' => 'response-123',
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'decision' => 'review',
                                'risk_score' => 0.72,
                                'violations' => [[
                                    'code' => 'wrong_category',
                                    'confidence' => 0.81,
                                    'evidence' => 'Описание не соответствует категории.',
                                ]],
                                'seller_message' => 'Требуется ручная проверка.',
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $result = app(GoogleGeminiModerationClient::class)->moderate($this->payload());

        $this->assertSame('review', $result->decision);
        $this->assertSame(0.72, $result->riskScore);
        $this->assertSame('wrong_category', $result->violations[0]['code']);
        $this->assertSame('response-123', $result->providerRequestId);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url()
                === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model:generateContent'
                && $request->hasHeader('x-goog-api-key', 'test-secret-key')
                && ! str_contains($request->url(), 'test-secret-key')
                && data_get($body, 'generationConfig.responseMimeType') === 'application/json'
                && data_get($body, 'generationConfig.responseJsonSchema.properties.decision.enum')
                    === ['allow', 'review', 'block'];
        });
    }

    public function test_client_rejects_http_error_without_exposing_response_body(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'secret details']], 429)]);

        $this->expectException(AiModerationProviderException::class);
        $this->expectExceptionMessage('HTTP 429');

        app(GoogleGeminiModerationClient::class)->moderate($this->payload());
    }

    public function test_each_retry_passes_through_rate_limiter(): void
    {
        config()->set('moderation.google.retry_times', 1);
        $throttle = new class extends GeminiRequestThrottle
        {
            public int $calls = 0;

            public function wait(): void
            {
                $this->calls++;
            }
        };
        $this->app->instance(GeminiRequestThrottle::class, $throttle);
        Http::fakeSequence()
            ->push([], 503)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'decision' => 'allow',
                            'risk_score' => 0,
                            'violations' => [],
                            'seller_message' => null,
                        ]),
                    ]]],
                ]],
            ]);

        app(GoogleGeminiModerationClient::class)->moderate($this->payload());

        $this->assertSame(2, $throttle->calls);
    }

    public function test_client_rejects_invalid_json(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{invalid']]],
                ]],
            ]),
        ]);

        $this->expectException(AiModerationProviderException::class);
        $this->expectExceptionMessage('invalid JSON');

        app(GoogleGeminiModerationClient::class)->moderate($this->payload());
    }

    public function test_client_rejects_invalid_structure(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'decision' => 'allow',
                            'risk_score' => 'invalid',
                            'violations' => [],
                            'seller_message' => null,
                        ]),
                    ]]],
                ]],
            ]),
        ]);

        $this->expectException(AiModerationProviderException::class);
        $this->expectExceptionMessage('failed validation');

        app(GoogleGeminiModerationClient::class)->moderate($this->payload());
    }

    public function test_client_requires_api_key(): void
    {
        config()->set('moderation.google.api_key', null);

        $this->expectException(AiModerationProviderException::class);
        $this->expectExceptionMessage('not configured');

        app(GoogleGeminiModerationClient::class)->moderate($this->payload());
    }

    public function test_client_rejects_unsafe_base_url_without_http_request(): void
    {
        config()->set('moderation.google.base_url', 'https://127.0.0.1/v1beta');
        Http::fake();

        try {
            app(GoogleGeminiModerationClient::class)->moderate($this->payload());
            $this->fail('Unsafe URL was accepted.');
        } catch (AiModerationProviderException $exception) {
            $this->assertFalse($exception->isRetryable());
            $this->assertStringContainsString('unsafe', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_client_sends_product_image_as_inline_data(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'decision' => 'allow',
                        'risk_score' => 0,
                        'violations' => [],
                        'seller_message' => null,
                    ]),
                ]]],
            ]],
        ])]);

        $payload = $this->payload(['products/test.png']);
        $result = app(GoogleGeminiModerationClient::class)->moderate($payload);

        $this->assertTrue($result->imagesAnalyzed);
        Http::assertSent(fn (Request $request): bool => data_get(
            $request->data(),
            'contents.0.parts.1.inlineData.mimeType'
        ) === 'image/png');
    }

    public function test_client_omits_images_when_separate_image_moderation_is_enabled(): void
    {
        config()->set('moderation.images.enabled', true);
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'decision' => 'allow',
                        'risk_score' => 0,
                        'violations' => [],
                        'seller_message' => null,
                    ]),
                ]]],
            ]],
        ])]);

        $result = app(GoogleGeminiModerationClient::class)
            ->moderate($this->payload(['products/test.png']));

        $this->assertFalse($result->imagesAnalyzed);
        Http::assertSent(fn (Request $request): bool => data_get(
            $request->data(),
            'contents.0.parts.1.inlineData'
        ) === null);
    }

    private function payload(array $imagePaths = []): ProductModerationPayload
    {
        return new ProductModerationPayload(
            productId: 10,
            contentVersion: 2,
            fingerprint: hash('sha256', 'payload'),
            name: 'Тестовый товар',
            description: 'Подробное описание тестового товара.',
            features: ['Новый'],
            categoryId: 5,
            categoryName: 'Тестовая категория',
            imagePaths: $imagePaths
        );
    }
}

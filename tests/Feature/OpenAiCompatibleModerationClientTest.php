<?php

namespace Tests\Feature;

use App\Data\ProductModerationPayload;
use App\Exceptions\AiModerationProviderException;
use App\Services\OpenAiCompatibleModerationClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpenAiCompatibleModerationClientTest extends TestCase
{
    public function test_client_sends_compatible_request_and_parses_response(): void
    {
        config()->set('moderation.openai_compatible.api_key', 'test-key');
        config()->set('moderation.openai_compatible.model', 'vendor/model');
        config()->set('moderation.openai_compatible.base_url', 'https://ai.example.test/v1');
        config()->set('moderation.openai_compatible.chat_completions_path', '/chat/completions');
        config()->set('moderation.http.requests_per_minute', 0);
        config()->set('moderation.http.retry_times', 0);

        Http::fake([
            '*' => Http::response([
                'id' => 'request-123',
                'choices' => [[
                    'message' => [
                        'content' => '```json
{"decision":"allow","risk_score":0.03,"violations":[],"seller_message":null}
```',
                    ],
                ]],
            ]),
        ]);

        $result = app(OpenAiCompatibleModerationClient::class)->moderate($this->payload());

        $this->assertSame('allow', $result->decision);
        $this->assertSame(0.03, $result->riskScore);
        $this->assertSame('request-123', $result->providerRequestId);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://ai.example.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'vendor/model'
            && data_get($request->data(), 'messages.0.role') === 'system'
            && is_string(data_get($request->data(), 'messages.1.content'))
            && data_get($request->data(), 'response_format.type') === 'json_object'
        );
    }

    public function test_client_sends_product_image_as_data_url(): void
    {
        $this->configureClient();
        config()->set('moderation.openai_compatible.vision_enabled', true);
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'choices' => [[
                'message' => ['content' => json_encode([
                    'decision' => 'allow',
                    'risk_score' => 0,
                    'violations' => [],
                    'seller_message' => null,
                ])],
            ]],
        ])]);

        $result = app(OpenAiCompatibleModerationClient::class)
            ->moderate($this->payload(['products/test.png']));

        $this->assertTrue($result->imagesAnalyzed);
        Http::assertSent(fn (Request $request): bool => str_starts_with(
            (string) data_get($request->data(), 'messages.1.content.1.image_url.url'),
            'data:image/png;base64,'
        ));
    }

    public function test_client_omits_product_images_when_provider_vision_is_disabled(): void
    {
        $this->configureClient();
        config()->set('moderation.openai_compatible.vision_enabled', false);
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'choices' => [[
                'message' => ['content' => json_encode([
                    'decision' => 'allow',
                    'risk_score' => 0,
                    'violations' => [],
                    'seller_message' => null,
                ])],
            ]],
        ])]);

        $result = app(OpenAiCompatibleModerationClient::class)
            ->moderate($this->payload(['products/test.png']));

        $this->assertFalse($result->imagesAnalyzed);
        Http::assertSent(fn (Request $request): bool => is_string(data_get($request->data(), 'messages.1.content')));
    }

    public function test_client_omits_images_when_separate_image_moderation_is_enabled(): void
    {
        $this->configureClient();
        config()->set('moderation.openai_compatible.vision_enabled', true);
        config()->set('moderation.images.enabled', true);
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'choices' => [[
                'message' => ['content' => json_encode([
                    'decision' => 'allow',
                    'risk_score' => 0,
                    'violations' => [],
                    'seller_message' => null,
                ])],
            ]],
        ])]);

        $result = app(OpenAiCompatibleModerationClient::class)
            ->moderate($this->payload(['products/test.png']));

        $this->assertFalse($result->imagesAnalyzed);
        Http::assertSent(fn (Request $request): bool => is_string(data_get($request->data(), 'messages.1.content')));
    }

    public function test_client_includes_api_error_body_in_exception_message(): void
    {
        $this->configureClient();
        Http::fake(['*' => Http::response([
            'error' => [
                'message' => 'Invalid request shape.',
                'type' => 'invalid_request_error',
            ],
        ], 400)]);

        try {
            app(OpenAiCompatibleModerationClient::class)->moderate($this->payload());
            $this->fail('Provider exception was not thrown.');
        } catch (AiModerationProviderException $exception) {
            $this->assertFalse($exception->isRetryable());
            $this->assertStringContainsString('HTTP 400', $exception->getMessage());
            $this->assertStringContainsString('Invalid request shape', $exception->getMessage());
        }
    }

    public function test_client_rejects_private_base_url_without_http_request(): void
    {
        $this->configureClient();
        config()->set('moderation.openai_compatible.base_url', 'https://10.0.0.10/v1');
        Http::fake();

        try {
            app(OpenAiCompatibleModerationClient::class)->moderate($this->payload());
            $this->fail('Unsafe URL was accepted.');
        } catch (AiModerationProviderException $exception) {
            $this->assertFalse($exception->isRetryable());
        }

        Http::assertNothingSent();
    }

    private function configureClient(): void
    {
        config()->set('moderation.openai_compatible.api_key', 'test-key');
        config()->set('moderation.openai_compatible.model', 'vendor/model');
        config()->set('moderation.openai_compatible.base_url', 'https://ai.example.test/v1');
        config()->set('moderation.openai_compatible.chat_completions_path', '/chat/completions');
        config()->set('moderation.http.requests_per_minute', 0);
        config()->set('moderation.http.retry_times', 0);
    }

    private function payload(array $imagePaths = []): ProductModerationPayload
    {
        return new ProductModerationPayload(
            productId: 10,
            contentVersion: 2,
            fingerprint: hash('sha256', 'payload'),
            name: 'Тестовый товар',
            description: 'Описание товара',
            features: ['Новый'],
            categoryId: 5,
            categoryName: 'Категория',
            imagePaths: $imagePaths
        );
    }
}

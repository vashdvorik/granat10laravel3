<?php

namespace Tests\Feature;

use App\Data\ProductModerationPayload;
use App\Exceptions\AiModerationProviderException;
use App\Services\GoogleGeminiImageModerationClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleGeminiImageModerationClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moderation.images.google.api_key', 'test-key');
        config()->set('moderation.images.google.model', 'gemini-image-test');
        config()->set('moderation.images.google.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        config()->set('moderation.http.requests_per_minute', 0);
        Storage::fake('public');
    }

    public function test_client_sends_images_and_parses_response(): void
    {
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'responseId' => 'image-request-1',
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'decision' => 'allow',
                            'risk_score' => 0.01,
                            'violations' => [],
                            'seller_message' => null,
                        ]),
                    ]],
                ],
            ]],
        ])]);

        $result = app(GoogleGeminiImageModerationClient::class)->moderate($this->payload(['products/test.png']));

        $this->assertSame('allow', $result->decision);
        $this->assertTrue($result->imagesAnalyzed);
        $this->assertSame('image-request-1', $result->providerRequestId);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-image-test:generateContent'
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && data_get($request->data(), 'contents.0.parts.1.inlineData.mimeType') === 'image/png'
        );
    }

    public function test_client_returns_review_when_images_cannot_be_prepared(): void
    {
        Http::fake();

        $result = app(GoogleGeminiImageModerationClient::class)->moderate($this->payload(['products/missing.png']));

        $this->assertSame('review', $result->decision);
        $this->assertSame('empty_image', $result->violations[0]['code']);
        $this->assertFalse($result->imagesAnalyzed);
        Http::assertNothingSent();
    }

    public function test_client_exposes_safe_http_error_body(): void
    {
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'error' => ['message' => 'Invalid image request.'],
        ], 400)]);

        try {
            app(GoogleGeminiImageModerationClient::class)->moderate($this->payload(['products/test.png']));
            $this->fail('Provider exception was not thrown.');
        } catch (AiModerationProviderException $exception) {
            $this->assertFalse($exception->isRetryable());
            $this->assertStringContainsString('HTTP 400', $exception->getMessage());
            $this->assertStringContainsString('Invalid image request', $exception->getMessage());
        }
    }

    private function payload(array $imagePaths): ProductModerationPayload
    {
        return new ProductModerationPayload(
            productId: 10,
            contentVersion: 2,
            fingerprint: hash('sha256', 'payload'),
            name: 'Товар',
            description: 'Описание',
            features: [],
            categoryId: 5,
            categoryName: 'Категория',
            imagePaths: $imagePaths
        );
    }
}

<?php

namespace Tests\Feature;

use App\Data\ProductModerationPayload;
use App\Services\OpenAiModerationImageClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpenAiModerationClientTest extends TestCase
{
    public function test_image_client_calls_openai_moderations_endpoint_and_maps_safe_result(): void
    {
        $this->configureClient();
        Http::fake(['*' => Http::response([
            'id' => 'modr-safe-123',
            'results' => [[
                'flagged' => false,
                'categories' => ['violence' => false],
                'category_scores' => ['violence' => 0.02],
            ]],
        ])]);

        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));

        $result = app(OpenAiModerationImageClient::class)->moderate($this->payload(['products/test.png']));

        $this->assertSame('allow', $result->decision);
        $this->assertSame(0.02, $result->riskScore);
        $this->assertSame('modr-safe-123', $result->providerRequestId);
        $this->assertSame([], $result->violations);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/moderations'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'omni-moderation-latest'
            && data_get($request->data(), 'input.1.type') === 'image_url'
        );
    }

    public function test_client_sends_images_and_routes_flagged_content_to_manual_review(): void
    {
        $this->configureClient();
        Storage::fake('public');
        Storage::disk('public')->put('products/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='
        ));
        Http::fake(['*' => Http::response([
            'id' => 'modr-flagged-123',
            'results' => [[
                'flagged' => true,
                'categories' => ['violence' => true, 'sexual' => false],
                'category_scores' => ['violence' => 0.86, 'sexual' => 0.01],
                'category_applied_input_types' => ['violence' => ['image']],
            ]],
        ])]);

        $result = app(OpenAiModerationImageClient::class)
            ->moderate($this->payload(['products/test.png']));

        $this->assertSame('review', $result->decision);
        $this->assertSame(0.86, $result->riskScore);
        $this->assertTrue($result->imagesAnalyzed);
        $this->assertSame('unsafe_image', $result->violations[0]['code']);
        $this->assertSame('images', $result->violations[0]['field']);

        Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'input.1.type') === 'image_url'
            && str_starts_with((string) data_get($request->data(), 'input.1.image_url.url'), 'data:image/png;base64,')
        );
    }

    private function configureClient(): void
    {
        config()->set('moderation.images.openai_moderation.api_key', 'test-key');
        config()->set('moderation.images.openai_moderation.model', 'omni-moderation-latest');
        config()->set('moderation.images.openai_moderation.base_url', 'https://api.openai.com/v1');
        config()->set('moderation.http.requests_per_minute', 0);
        config()->set('moderation.http.retry_times', 0);
    }

    /**
     * @param  array<int, string>  $imagePaths
     */
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
            imagePaths: $imagePaths,
        );
    }
}

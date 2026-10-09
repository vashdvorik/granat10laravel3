<?php

namespace Tests\Feature;

use App\Contracts\AiModerationClient;
use App\Contracts\ProductImageModerationClient;
use App\Data\ProductModerationPayload;
use App\Exceptions\AiModerationProviderException;
use App\Services\OpenRouterImageModerationClient;
use App\Services\OpenRouterModerationClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Проверка текста и проверка фото: один сервис (OpenRouter), разные модели и сообщения. */
class OpenRouterModerationClientsTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
        config()->set('moderation.openrouter.api_key', 'sk-or-test');
        config()->set('moderation.openrouter.base_url', 'https://openrouter.test/api/v1');
        config()->set('moderation.openrouter.text_model', 'vendor/text-model');
        config()->set('moderation.openrouter.image_model', 'vendor/vision-model');
        config()->set('moderation.http.requests_per_minute', 0);
        config()->set('moderation.http.retry_times', 0);
    }

    /** @param  array<int, string>  $imagePaths */
    private function payload(array $imagePaths = []): ProductModerationPayload
    {
        return new ProductModerationPayload(
            productId: 7,
            contentVersion: 2,
            fingerprint: 'fp',
            name: 'Чайник электрический',
            description: 'Хороший чайник',
            features: ['1,7 л', 'стальной корпус'],
            categoryId: 3,
            categoryName: 'Посуда',
            imagePaths: $imagePaths,
        );
    }

    private function answer(array $result): array
    {
        return ['id' => 'gen-42', 'choices' => [['message' => ['content' => json_encode($result)]]]];
    }

    // ------------------------------------------------------------------ контейнер

    public function test_both_contracts_resolve_to_openrouter_clients(): void
    {
        $this->assertInstanceOf(OpenRouterModerationClient::class, app(AiModerationClient::class));
        $this->assertInstanceOf(OpenRouterImageModerationClient::class, app(ProductImageModerationClient::class));
    }

    // ------------------------------------------------------------------ текст

    public function test_text_client_sends_product_data_to_the_text_model_and_parses_the_verdict(): void
    {
        Http::fake(['*' => Http::response($this->answer([
            'decision' => 'allow', 'risk_score' => 0.04, 'violations' => [], 'seller_message' => null,
        ]))]);

        $result = app(OpenRouterModerationClient::class)->moderate($this->payload());

        $this->assertSame('allow', $result->decision);
        $this->assertSame(0.04, $result->riskScore);
        $this->assertSame('gen-42', $result->providerRequestId);
        $this->assertTrue($result->imagesAnalyzed, 'у товара без фото анализировать нечего');

        Http::assertSent(function (Request $request): bool {
            $product = json_decode((string) data_get($request->data(), 'messages.1.content'), true);

            return $request->url() === 'https://openrouter.test/api/v1/chat/completions'
                && $request['model'] === 'vendor/text-model'
                && data_get($request->data(), 'messages.0.role') === 'system'
                && str_contains((string) data_get($request->data(), 'messages.0.content'), 'Верни только JSON')
                && $product['name'] === 'Чайник электрический'
                && $product['category'] === 'Посуда'
                && $product['features'] === ['1,7 л', 'стальной корпус'];
        });
    }

    public function test_text_client_never_sends_photos_and_does_not_count_them_as_checked(): void
    {
        Storage::disk('public')->put('products/a.png', base64_decode(self::PNG));
        Http::fake(['*' => Http::response($this->answer([
            'decision' => 'allow', 'risk_score' => 0, 'violations' => [], 'seller_message' => null,
        ]))]);

        $result = app(OpenRouterModerationClient::class)->moderate($this->payload(['products/a.png']));

        $this->assertFalse($result->imagesAnalyzed);
        Http::assertSent(fn (Request $request): bool => is_string(data_get($request->data(), 'messages.1.content')));
    }

    public function test_text_client_turns_violations_into_a_result(): void
    {
        Http::fake(['*' => Http::response($this->answer([
            'decision' => 'review',
            'risk_score' => 0.7,
            'violations' => [[
                'code' => 'spam', 'confidence' => 0.9, 'evidence' => 'Набор символов.',
                'field' => 'name', 'suggestion' => 'Напишите нормальное название.',
            ]],
            'seller_message' => 'Исправьте название.',
        ]))]);

        $result = app(OpenRouterModerationClient::class)->moderate($this->payload());

        $this->assertSame('review', $result->decision);
        $this->assertSame('spam', $result->violations[0]['code']);
        $this->assertSame('name', $result->violations[0]['field']);
    }

    public function test_text_client_rejects_a_verdict_in_the_wrong_format(): void
    {
        Http::fake(['*' => Http::response($this->answer(['decision' => 'maybe']))]);

        $this->expectException(AiModerationProviderException::class);
        app(OpenRouterModerationClient::class)->moderate($this->payload());
    }

    // ------------------------------------------------------------------ фото

    public function test_image_client_sends_photos_as_data_urls_to_the_image_model(): void
    {
        Storage::disk('public')->put('products/a.png', base64_decode(self::PNG));
        Storage::disk('public')->put('products/b.png', base64_decode(self::PNG));
        Http::fake(['*' => Http::response($this->answer([
            'decision' => 'allow', 'risk_score' => 0.01, 'violations' => [], 'seller_message' => null,
        ]))]);

        $result = app(OpenRouterImageModerationClient::class)
            ->moderate($this->payload(['products/a.png', 'products/b.png']));

        $this->assertSame('allow', $result->decision);
        $this->assertTrue($result->imagesAnalyzed);

        Http::assertSent(function (Request $request): bool {
            $content = data_get($request->data(), 'messages.1.content');

            return $request['model'] === 'vendor/vision-model'
                && $request->url() === 'https://openrouter.test/api/v1/chat/completions'
                && $content[0]['type'] === 'text'
                && $content[1]['type'] === 'image_url'
                && str_starts_with($content[1]['image_url']['url'], 'data:image/png;base64,')
                && $content[2]['type'] === 'image_url'
                && count($content) === 3;
        });
    }

    public function test_image_client_marks_photos_as_not_fully_checked_when_some_were_skipped(): void
    {
        Storage::disk('public')->put('products/a.png', base64_decode(self::PNG));
        Http::fake(['*' => Http::response($this->answer([
            'decision' => 'allow', 'risk_score' => 0, 'violations' => [], 'seller_message' => null,
        ]))]);

        // второго файла на диске нет: проверено 1 фото из 2
        $result = app(OpenRouterImageModerationClient::class)
            ->moderate($this->payload(['products/a.png', 'products/missing.png']));

        $this->assertFalse($result->imagesAnalyzed);
    }

    public function test_image_client_sends_nothing_when_no_photo_can_be_prepared(): void
    {
        Http::fake();

        $result = app(OpenRouterImageModerationClient::class)->moderate($this->payload(['products/missing.png']));

        $this->assertSame('review', $result->decision);
        $this->assertFalse($result->imagesAnalyzed);
        $this->assertSame('empty_image', $result->violations[0]['code']);
        $this->assertStringContainsString('Загрузите корректное изображение', (string) $result->sellerMessage);
        Http::assertNothingSent();
    }

    public function test_image_client_skips_files_that_are_not_supported_images(): void
    {
        Storage::disk('public')->put('products/note.txt', 'not an image');
        Http::fake();

        $result = app(OpenRouterImageModerationClient::class)->moderate($this->payload(['products/note.txt']));

        $this->assertSame('empty_image', $result->violations[0]['code']);
        Http::assertNothingSent();
    }
}

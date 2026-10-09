<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductImagesJob;
use App\Jobs\ModerateProductJob;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Весь путь товара через настоящие клиенты: и текст, и фото проверяются в OpenRouter
 * (HTTP подменён), а итог попадает в карточку и в журнал проверок.
 */
class OpenRouterModerationPipelineTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
        config()->set('queue.default', 'sync');
        config()->set('moderation.ai_enabled', true);
        config()->set('moderation.mode', 'auto');
        config()->set('moderation.auto_approve.excluded_category_aliases', []);
        config()->set('moderation.auto_approve.max_risk_score', 0.2);
        config()->set('moderation.images.enabled', true);
        config()->set('moderation.images.local_scan.enabled', false);
        config()->set('moderation.openrouter.api_key', 'sk-or-test');
        config()->set('moderation.openrouter.base_url', 'https://openrouter.test/api/v1');
        config()->set('moderation.openrouter.text_model', 'vendor/text-model');
        config()->set('moderation.openrouter.image_model', 'vendor/vision-model');
        config()->set('moderation.http.requests_per_minute', 0);
        config()->set('moderation.http.retry_times', 0);
    }

    private function product(): Product
    {
        $product = Product::factory()->pendingModeration()->create([
            'is_active' => false,
            'publication_requested' => true,
            'moderation_version' => 3,
        ]);
        $image = Image::factory()->for($product)->create();
        Storage::disk('public')->put($image->path, base64_decode(self::PNG));

        // решение локальных правил, без него автопубликация запрещена
        ProductModerationRun::query()->create([
            'product_id' => $product->id,
            'content_version' => 3,
            'trigger' => 'seller_submit',
            'provider' => 'local',
            'status' => 'completed',
            'decision' => 'allow',
            'risk_score' => 0,
            'request_fingerprint' => hash('sha256', 'local-'.$product->id),
        ]);

        return $product;
    }

    /** @param  array<string, mixed>  $overrides */
    private function verdict(array $overrides = []): array
    {
        return [
            'id' => 'gen-'.uniqid(),
            'choices' => [['message' => ['content' => json_encode($overrides + [
                'decision' => 'allow', 'risk_score' => 0.02, 'violations' => [], 'seller_message' => null,
            ])]]],
        ];
    }

    public function test_text_and_photos_are_both_checked_in_openrouter_and_the_product_is_published(): void
    {
        $product = $this->product();
        Http::fake(['*' => Http::sequence()->push($this->verdict())->push($this->verdict())]);

        ModerateProductJob::dispatchSync($product->id, 3);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::Approved, $product->moderation_status);
        $this->assertTrue((bool) $product->is_active);
        $this->assertSame(3, $product->moderated_version);

        // два запроса, оба к OpenRouter: сначала текст своей моделью, потом фото своей
        Http::assertSentCount(2);
        $models = [];
        Http::assertSent(function (Request $request) use (&$models): bool {
            $models[] = $request['model'];

            return $request->url() === 'https://openrouter.test/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-or-test');
        });
        $this->assertSame(['vendor/text-model', 'vendor/vision-model'], $models);

        $text = ProductModerationRun::query()->where('product_id', $product->id)->where('provider', 'openrouter')->sole();
        $this->assertSame('completed', $text->status);
        $this->assertSame('vendor/text-model', $text->model);

        $images = ProductModerationRun::query()->where('product_id', $product->id)->where('provider', 'image_openrouter')->sole();
        $this->assertSame('completed', $images->status);
        $this->assertSame('vendor/vision-model', $images->model);
        $this->assertSame('allow', $images->decision);
    }

    public function test_photo_violation_found_by_openrouter_is_shown_to_the_seller(): void
    {
        $product = $this->product();
        Http::fake(['*' => Http::sequence()->push($this->verdict())->push($this->verdict([
            'decision' => 'review',
            'risk_score' => 0.8,
            'violations' => [[
                'code' => 'wrong_category', 'confidence' => 0.9, 'evidence' => 'На фото не тот товар.',
                'field' => 'images', 'suggestion' => 'Загрузите фото самого товара.',
            ]],
            'seller_message' => 'Загрузите фото самого товара.',
        ]))]);

        ModerateProductJob::dispatchSync($product->id, 3);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::ChangesRequired, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertStringContainsString('Загрузите фото самого товара', (string) $product->moderation_summary);
    }

    public function test_text_violation_found_by_openrouter_stops_the_pipeline_before_photos(): void
    {
        $product = $this->product();
        Http::fake(['*' => Http::response($this->verdict([
            'decision' => 'review',
            'risk_score' => 0.9,
            'violations' => [[
                'code' => 'spam', 'confidence' => 0.9, 'evidence' => 'Это спам.',
                'field' => 'name', 'suggestion' => 'Напишите нормальное название.',
            ]],
            'seller_message' => 'Исправьте название.',
        ]))]);

        ModerateProductJob::dispatchSync($product->id, 3);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::ChangesRequired, $product->moderation_status);
        Http::assertSentCount(1);
    }

    public function test_without_a_key_the_product_goes_to_a_moderator_and_the_reason_is_logged(): void
    {
        config()->set('moderation.openrouter.api_key', '');
        $product = $this->product();
        Http::fake();

        ModerateProductJob::dispatchSync($product->id, 3);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        Http::assertNothingSent();

        $run = ProductModerationRun::query()->where('provider', 'openrouter')->sole();
        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('Ключ OpenRouter не задан', (string) $run->error_message);
    }

    public function test_rejected_key_sends_the_product_to_a_moderator_with_a_clear_reason(): void
    {
        $product = $this->product();
        Http::fake(['*' => Http::response(['error' => ['message' => 'No auth credentials found', 'code' => 401]], 401)]);

        ModerateProductJob::dispatchSync($product->id, 3);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);

        $run = ProductModerationRun::query()->where('provider', 'openrouter')->sole();
        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('отклонил ключ', (string) $run->error_message);
        Http::assertSentCount(1);
    }

    public function test_exhausted_balance_on_photos_sends_the_product_to_a_moderator(): void
    {
        $product = $this->product();
        Http::fake(['*' => Http::sequence()
            ->push($this->verdict())
            ->push(['error' => ['message' => 'Insufficient credits', 'code' => 402]], 402)]);

        ModerateProductJob::dispatchSync($product->id, 3);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);

        $run = ProductModerationRun::query()->where('provider', 'image_openrouter')->sole();
        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('недостаточно средств', (string) $run->error_message);
    }

    public function test_jobs_pin_the_models_chosen_at_dispatch_time(): void
    {
        Queue::fake();
        $product = $this->product();

        config()->set('moderation.openrouter.text_model', 'vendor/changed-later');
        ModerateProductJob::dispatch($product->id, 3, 'vendor/pinned-text', 'auto');

        Queue::assertPushed(ModerateProductJob::class, fn (ModerateProductJob $job): bool => $job->model === 'vendor/pinned-text');

        Queue::fake([]);
        Http::fake(['*' => Http::sequence()->push($this->verdict())]);
        config()->set('moderation.images.enabled', false);

        (new ModerateProductJob($product->id, 3, 'vendor/pinned-text', 'auto'))->handle(
            app(\App\Contracts\AiModerationClient::class),
            app(\App\Services\ProductModerationPayloadFactory::class),
            app(\App\Services\ProductAutoApprovalPolicy::class),
        );

        Http::assertSent(fn (Request $request): bool => $request['model'] === 'vendor/pinned-text');
        $this->assertSame(
            'vendor/pinned-text',
            ProductModerationRun::query()->where('provider', 'openrouter')->value('model'),
        );
    }

    public function test_image_job_queues_use_the_image_settings(): void
    {
        config()->set('moderation.images.queue', 'photos');
        config()->set('moderation.images.timeout', 45);
        config()->set('moderation.images.max_attempts', 2);

        $job = new ModerateProductImagesJob(1, 1);

        $this->assertSame('photos', $job->queue);
        $this->assertSame(45, $job->timeout);
        $this->assertSame(2, $job->tries);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductJob;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ProductModerationRetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductModerationRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_restores_intent_and_dispatches_current_version(): void
    {
        Queue::fake();
        config()->set('moderation.ai_enabled', true);
        $product = $this->productAwaitingManualReviewAfterFailure();

        $retried = app(ProductModerationRetryService::class)->retry($product);

        $this->assertTrue($retried);
        $product->refresh();
        $this->assertFalse((bool) $product->is_active);
        $this->assertTrue($product->publication_requested);
        $this->assertSame(ProductModerationStatus::Pending, $product->moderation_status);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'status' => 'retrying',
        ]);
        Queue::assertPushed(
            ModerateProductJob::class,
            fn (ModerateProductJob $job): bool => $job->productId === $product->id
                && $job->contentVersion === $product->moderation_version
        );
    }

    public function test_retry_is_blocked_when_ai_is_disabled(): void
    {
        Queue::fake();
        config()->set('moderation.ai_enabled', false);
        $product = $this->productAwaitingManualReviewAfterFailure();

        $this->assertFalse(app(ProductModerationRetryService::class)->retry($product));
        Queue::assertNotPushed(ModerateProductJob::class);
    }

    public function test_command_requires_enabled_feature_without_force(): void
    {
        config()->set('moderation.ai_enabled', false);

        $this->artisan('moderation:retry-failed')
            ->expectsOutput('AI moderation is disabled.')
            ->assertFailed();
    }

    public function test_command_respects_limit(): void
    {
        Queue::fake();
        config()->set('moderation.ai_enabled', true);
        $first = $this->productAwaitingManualReviewAfterFailure();
        $second = $this->productAwaitingManualReviewAfterFailure();

        $this->artisan('moderation:retry-failed --limit=1')
            ->expectsOutput('Dispatched 1 failed moderation checks.')
            ->assertSuccessful();

        Queue::assertPushed(ModerateProductJob::class, 1);
        $this->assertSame(ProductModerationStatus::NeedsReview, $first->fresh()->moderation_status);
        $this->assertSame(ProductModerationStatus::Pending, $second->fresh()->moderation_status);
    }

    public function test_command_skips_stale_failed_runs(): void
    {
        Queue::fake();
        config()->set('moderation.ai_enabled', true);
        $product = $this->productAwaitingManualReviewAfterFailure();
        $product->forceFill(['moderation_version' => $product->moderation_version + 1])->save();

        $this->artisan('moderation:retry-failed --limit=1')
            ->expectsOutput('Dispatched 0 failed moderation checks.')
            ->assertSuccessful();

        Queue::assertNotPushed(ModerateProductJob::class);
        $this->assertSame(ProductModerationStatus::NeedsReview, $product->fresh()->moderation_status);
    }

    private function productAwaitingManualReviewAfterFailure(): Product
    {
        $product = Product::factory()->create([
            'is_active' => false,
            'publication_requested' => true,
            'moderation_status' => ProductModerationStatus::NeedsReview,
            'moderated_version' => null,
        ]);
        ProductModerationRun::query()->create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'queue',
            'provider' => 'openrouter',
            'status' => 'failed',
            'request_fingerprint' => hash('sha256', 'retry-'.$product->id),
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        return $product;
    }
}

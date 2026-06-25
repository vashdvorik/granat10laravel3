<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductImagesJob;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ProductImageModerationRetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductImageModerationRetryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_dispatches_image_moderation_job_for_problem_image_run(): void
    {
        Queue::fake();
        config()->set('moderation.images.enabled', true);

        $product = Product::factory()->create([
            'moderation_status' => ProductModerationStatus::NeedsReview,
            'moderation_version' => 5,
            'is_active' => false,
        ]);
        Image::create([
            'product_id' => $product->id,
            'path' => 'products/problem.jpg',
            'is_main' => true,
            'order' => 1,
        ]);
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 5,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'decision' => 'review',
            'request_fingerprint' => hash('sha256', 'image-review'),
        ]);

        $retried = app(ProductImageModerationRetryService::class)->retry($product);

        $this->assertTrue($retried);
        $this->assertSame(ProductModerationStatus::Processing, $product->refresh()->moderation_status);
        $this->assertSame('retrying', ProductModerationRun::query()->first()->status);
        Queue::assertPushed(
            ModerateProductImagesJob::class,
            fn (ModerateProductImagesJob $job): bool => $job->productId === $product->id
                && $job->contentVersion === 5
        );
    }

    public function test_retry_is_not_available_for_allowed_image_run(): void
    {
        Queue::fake();
        config()->set('moderation.images.enabled', true);

        $product = Product::factory()->create([
            'moderation_status' => ProductModerationStatus::NeedsReview,
            'moderation_version' => 2,
        ]);
        Image::create([
            'product_id' => $product->id,
            'path' => 'products/ok.jpg',
            'is_main' => true,
            'order' => 1,
        ]);
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 2,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'decision' => 'allow',
            'request_fingerprint' => hash('sha256', 'image-allow'),
        ]);

        $retried = app(ProductImageModerationRetryService::class)->retry($product);

        $this->assertFalse($retried);
        Queue::assertNotPushed(ModerateProductImagesJob::class);
    }
}

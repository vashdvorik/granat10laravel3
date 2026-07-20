<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ModerationOperationsStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationOperationsStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_counts_failed_and_stuck_runs(): void
    {
        config()->set('queue.default', 'sync');
        config()->set('moderation.images.enabled', true);
        config()->set('moderation.images.provider', 'gemini');
        config()->set('moderation.images.api_key', 'test-image-key');
        config()->set('moderation.images.model', 'gemini-3.1-flash-lite');
        config()->set('moderation.images.queue', 'moderation');
        $product = Product::factory()->create();

        $this->createRun($product, 'failed', now());
        $this->createRun($product, 'processing', now()->subMinutes(15));
        $this->createRun($product, 'processing', now());
        $this->createRun($product, 'failed', now(), 'image_google');
        $this->createRun($product, 'processing', now()->subMinutes(15), 'image_google');

        $status = app(ModerationOperationsStatus::class)->current();

        $this->assertTrue($status['queue_ok']);
        $this->assertTrue($status['image_moderation_enabled']);
        $this->assertTrue($status['image_api_key_configured']);
        $this->assertSame('gemini-3.1-flash-lite', $status['image_model']);
        $this->assertSame('moderation', $status['image_queue']);
        $this->assertSame(2, $status['failed_runs']);
        $this->assertSame(2, $status['stuck_runs']);
        $this->assertSame(1, $status['failed_image_runs']);
        $this->assertSame(1, $status['stuck_image_runs']);
    }

    private function createRun(
        Product $product,
        string $status,
        \DateTimeInterface $startedAt,
        string $provider = 'test'
    ): void {
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'queue',
            'provider' => $provider,
            'status' => $status,
            'request_fingerprint' => hash('sha256', $provider.$status.$startedAt->format('c')),
            'started_at' => $startedAt,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Events\ProductModerationDecided;
use App\Listeners\SendProductModerationDecisionNotification;
use App\Models\Product;
use App\Models\Seller;
use App\Notifications\ProductModerationDecisionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProductModerationEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_decision_dispatches_event(): void
    {
        Event::fake([ProductModerationDecided::class]);
        $seller = Seller::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'moderation_status' => ProductModerationStatus::NeedsReview,
        ]);

        app(\App\Services\ProductModerationService::class)
            ->requestChanges($product, $seller->user, 'Исправьте описание.');

        Event::assertDispatched(
            ProductModerationDecided::class,
            fn (ProductModerationDecided $event): bool => $event->productId === $product->id
        );
    }

    public function test_listener_notifies_product_owner(): void
    {
        Notification::fake();
        $seller = Seller::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_summary' => 'Исправьте описание.',
        ]);

        app(SendProductModerationDecisionNotification::class)
            ->handle(new ProductModerationDecided($product->id));

        Notification::assertSentTo(
            $seller->user,
            ProductModerationDecisionNotification::class
        );
    }
}

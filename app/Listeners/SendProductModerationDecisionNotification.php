<?php

namespace App\Listeners;

use App\Events\ProductModerationDecided;
use App\Models\Product;
use App\Notifications\ProductModerationDecisionNotification;

class SendProductModerationDecisionNotification
{
    public function handle(ProductModerationDecided $event): void
    {
        $product = Product::query()
            ->with('seller.user')
            ->find($event->productId);

        $product?->seller?->user?->notify(
            new ProductModerationDecisionNotification($product)
        );
    }
}

<?php

namespace App\Services;

use App\Data\ProductModerationPayload;
use App\Models\Product;

class ProductModerationPayloadFactory
{
    public function __construct(
        private readonly ProductLocalModerationAnalyzer $localAnalyzer
    ) {
    }

    public function make(Product $product): ProductModerationPayload
    {
        $product->loadMissing(['category', 'images']);

        return new ProductModerationPayload(
            productId: $product->id,
            contentVersion: $product->moderation_version,
            fingerprint: $this->localAnalyzer->fingerprint($product),
            name: $product->name,
            description: $product->desc,
            features: collect([
                $product->features1,
                $product->features2,
                $product->features3,
                $product->features4,
            ])->filter()->values()->all(),
            categoryId: $product->category_id,
            categoryName: $product->category?->name ?? '',
            imagePaths: $product->images->sortBy('order')->pluck('path')->values()->all(),
        );
    }
}

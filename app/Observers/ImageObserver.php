<?php

namespace App\Observers;

use App\Models\Image;
use App\Services\CatalogCacheService;

class ImageObserver
{
    public function __construct(private readonly CatalogCacheService $cache)
    {
    }

    public function deleted(Image $image): void
    {
        $product = $image->product;

        if (!$product || !$product->is_active || $product->images()->exists()) {
            return;
        }

        $this->cache->forgetProductRelated($product);
    }
}

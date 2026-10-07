<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CatalogCacheService;

class ProductObserver
{
    public function __construct(private readonly CatalogCacheService $cache)
    {
    }

    public function saving(Product $product): void
    {
        // Срок VIP имеет смысл только при включённом VIP
        if (! $product->is_vip) {
            $product->vip_until = null;
        }
    }

    public function created(Product $product): void
    {
        $this->cache->forgetProductRelated($product);
    }

    public function updated(Product $product): void
    {
        $this->cache->forgetProductRelated(
            $product,
            oldCategoryId: $product->getOriginal('category_id'),
            oldSellerId: $product->getOriginal('seller_id')
        );
    }

    public function deleted(Product $product): void
    {
        $this->cache->forgetProductRelated($product);
    }
}

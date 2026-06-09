<?php

namespace App\Observers;

use App\Models\Seller;
use App\Services\CatalogCacheService;

class SellerObserver
{
    public function __construct(private readonly CatalogCacheService $cache)
    {
    }

    public function created(Seller $seller): void
    {
        $this->cache->forgetSellerRelated($seller);
    }

    public function updated(Seller $seller): void
    {
        $this->cache->forgetSellerRelated(
            $seller,
            visibilityChanged: $seller->wasChanged('settlement_id')
        );
    }

    public function deleting(Seller $seller): void
    {
        $this->cache->forgetSellerRelated($seller, visibilityChanged: true);
    }
}

<?php

namespace App\Observers;

use App\Models\SellerPackage;
use App\Services\CatalogCacheService;

class SellerPackageObserver
{
    public function __construct(private readonly CatalogCacheService $cache)
    {
    }

    public function saved(SellerPackage $sellerPackage): void
    {
        $this->cache->forgetSellerPackageRelated($sellerPackage);
    }

    public function deleted(SellerPackage $sellerPackage): void
    {
        $this->cache->forgetSellerPackageRelated($sellerPackage);
    }
}

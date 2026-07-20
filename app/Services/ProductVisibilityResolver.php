<?php

namespace App\Services;

use App\Data\ProductVisibilitySnapshot;
use App\Enums\ProductModerationStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductVisibilityResolver
{
    public function snapshot(Product $product): ProductVisibilitySnapshot
    {
        $product->loadMissing('seller');

        $isActive = (bool) $product->is_active;
        $isModerationApproved = $product->moderation_status === ProductModerationStatus::Approved;
        $isModerationCurrent = $isModerationApproved
            && $product->moderated_version === $product->moderation_version;
        $hasImages = $this->hasImages($product);
        $hasActivePackage = $this->hasActivePackage($product);
        $withinActiveLimit = $this->withinActiveLimit($product);
        $withinVipLimit = $this->withinVipLimit($product);
        $publicVisible = $isActive
            && $isModerationCurrent
            && $hasActivePackage;
        $buyerBlockingReasons = $this->computeBuyerBlockingReasons(
            isActive: $isActive,
            isModerationApproved: $isModerationApproved,
            isModerationCurrent: $isModerationCurrent,
            hasImages: $hasImages,
            hasActivePackage: $hasActivePackage,
            withinActiveLimit: $withinActiveLimit,
            withinVipLimit: $withinVipLimit,
        );

        return new ProductVisibilitySnapshot(
            publicVisible: $publicVisible,
            buyerVisible: $buyerBlockingReasons === [],
            isActive: $isActive,
            isModerationApproved: $isModerationApproved,
            isModerationCurrent: $isModerationCurrent,
            hasImages: $hasImages,
            hasActivePackage: $hasActivePackage,
            withinActiveLimit: $withinActiveLimit,
            withinVipLimit: $withinVipLimit,
            buyerBlockingReasons: $buyerBlockingReasons,
        );
    }

    public function isPublic(Product $product): bool
    {
        return $this->snapshot($product)->publicVisible;
    }

    public function buyerVisible(Product $product): bool
    {
        return $this->snapshot($product)->buyerVisible;
    }

    /**
     * @return array<int, string>
     */
    public function buyerBlockingReasons(Product $product): array
    {
        return $this->snapshot($product)->buyerBlockingReasons;
    }

    public function applyPublicVisibility(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('moderation_status', ProductModerationStatus::Approved->value)
            ->whereColumn('moderated_version', 'moderation_version')
            ->whereHas('seller.sellerPackages', function (Builder $sellerPackageQuery): void {
                $sellerPackageQuery
                    ->where('is_active', true)
                    ->where(function (Builder $dateQuery): void {
                        $dateQuery
                            ->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->toDateString());
                    });
            });
    }

    private function computeBuyerBlockingReasons(
        bool $isActive,
        bool $isModerationApproved,
        bool $isModerationCurrent,
        bool $hasImages,
        bool $hasActivePackage,
        bool $withinActiveLimit,
        bool $withinVipLimit,
    ): array {
        $reasons = [];

        if (! $isModerationApproved) {
            $reasons[] = 'moderation_not_approved';
        } elseif (! $isModerationCurrent) {
            $reasons[] = 'moderation_outdated';
        }

        if (! $isActive) {
            $reasons[] = 'not_enabled_by_seller';
        }

        if (! $hasImages) {
            $reasons[] = 'no_images';
        }

        if (! $hasActivePackage) {
            $reasons[] = 'no_active_package';
        }

        if (! $withinActiveLimit) {
            $reasons[] = 'active_limit_exceeded';
        }

        if (! $withinVipLimit) {
            $reasons[] = 'vip_limit_exceeded';
        }

        return array_values(array_unique($reasons));
    }

    private function hasImages(Product $product): bool
    {
        return $product->relationLoaded('images')
            ? $product->images->isNotEmpty()
            : $product->images()->exists();
    }

    private function hasActivePackage(Product $product): bool
    {
        return $product->seller?->activePackage() !== null;
    }

    private function withinActiveLimit(Product $product): bool
    {
        if (! $product->is_active || $product->seller === null) {
            return true;
        }

        $package = $product->seller->activePackage()?->package;
        $limit = $package?->active_products_limit;

        return $limit === null
            || $product->seller->products()->where('is_active', true)->count() <= $limit;
    }

    private function withinVipLimit(Product $product): bool
    {
        if (! $product->is_active || ! $product->is_vip || $product->seller === null) {
            return true;
        }

        $package = $product->seller->activePackage()?->package;
        $limit = $package?->vip_products_limit;

        return $limit === null
            || $product->seller->products()
                ->where('is_active', true)
                ->where('is_vip', true)
                ->count() <= $limit;
    }
}

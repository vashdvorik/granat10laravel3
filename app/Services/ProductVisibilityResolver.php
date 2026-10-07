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
        $isActive = (bool) $product->is_active;
        $isModerationApproved = $product->moderation_status === ProductModerationStatus::Approved;
        $isModerationCurrent = $isModerationApproved
            && $product->moderated_version === $product->moderation_version;
        $hasImages = $this->hasImages($product);
        $publicVisible = $isActive && $isModerationCurrent;
        $buyerBlockingReasons = $this->computeBuyerBlockingReasons(
            isActive: $isActive,
            isModerationApproved: $isModerationApproved,
            isModerationCurrent: $isModerationCurrent,
            hasImages: $hasImages,
        );

        return new ProductVisibilitySnapshot(
            publicVisible: $publicVisible,
            buyerVisible: $buyerBlockingReasons === [],
            isActive: $isActive,
            isModerationApproved: $isModerationApproved,
            isModerationCurrent: $isModerationCurrent,
            hasImages: $hasImages,
            buyerBlockingReasons: $buyerBlockingReasons,
        );
    }

    public function isPublic(Product $product): bool
    {
        return $this->snapshot($product)->publicVisible;
    }

    public function applyPublicVisibility(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('moderation_status', ProductModerationStatus::Approved->value)
            ->whereColumn('moderated_version', 'moderation_version');
    }

    private function computeBuyerBlockingReasons(
        bool $isActive,
        bool $isModerationApproved,
        bool $isModerationCurrent,
        bool $hasImages,
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

        return array_values(array_unique($reasons));
    }

    private function hasImages(Product $product): bool
    {
        return $product->relationLoaded('images')
            ? $product->images->isNotEmpty()
            : $product->images()->exists();
    }
}

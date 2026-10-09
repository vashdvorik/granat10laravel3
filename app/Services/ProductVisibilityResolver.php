<?php

namespace App\Services;

use App\Data\ProductVisibilitySnapshot;
use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Models\Seller;
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
        $sellerHidden = (bool) $product->seller?->isHidden();
        $publicVisible = $isActive && $isModerationCurrent && ! $sellerHidden;
        $buyerBlockingReasons = $this->computeBuyerBlockingReasons(
            isActive: $isActive,
            isModerationApproved: $isModerationApproved,
            isModerationCurrent: $isModerationCurrent,
            hasImages: $hasImages,
            sellerHidden: $sellerHidden,
        );

        return new ProductVisibilitySnapshot(
            publicVisible: $publicVisible,
            buyerVisible: $buyerBlockingReasons === [],
            isActive: $isActive,
            isModerationApproved: $isModerationApproved,
            isModerationCurrent: $isModerationCurrent,
            hasImages: $hasImages,
            buyerBlockingReasons: $buyerBlockingReasons,
            sellerHidden: $sellerHidden,
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
            ->whereColumn('moderated_version', 'moderation_version')
            // Товары скрытого администратором магазина не показываются нигде: каталог, поиск, главная, карта сайта, заказы
            ->whereNotIn($query->qualifyColumn('seller_id'), Seller::query()->hiddenByAdmin()->select('id'));
    }

    private function computeBuyerBlockingReasons(
        bool $isActive,
        bool $isModerationApproved,
        bool $isModerationCurrent,
        bool $hasImages,
        bool $sellerHidden = false,
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

        if ($sellerHidden) {
            $reasons[] = 'seller_hidden';
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

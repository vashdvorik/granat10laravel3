<?php

namespace App\Services;

use App\Enums\ProductDisplayState;
use App\Enums\ProductModerationStatus;
use App\Models\Product;

class ProductDisplayStateResolver
{
    public function __construct(
        private readonly ProductStateTransitionService $transitions
    ) {}

    public function resolve(Product $product): ProductDisplayState
    {
        return match ($product->moderation_status) {
            ProductModerationStatus::ChangesRequired => ProductDisplayState::ChangesRequired,
            ProductModerationStatus::Rejected => ProductDisplayState::Rejected,
            ProductModerationStatus::NeedsReview,
            ProductModerationStatus::Failed => ProductDisplayState::ManualReview,
            ProductModerationStatus::Processing => ProductDisplayState::Checking,
            ProductModerationStatus::Pending => ProductDisplayState::Queued,
            ProductModerationStatus::Draft => ProductDisplayState::Draft,
            ProductModerationStatus::Approved => $this->approvedState($product),
        };
    }

    private function approvedState(Product $product): ProductDisplayState
    {
        if ($this->transitions->isPublic($product)) {
            return ProductDisplayState::Published;
        }

        if ($this->transitions->isHeldByHiddenSeller($product)) {
            return ProductDisplayState::SellerHidden;
        }

        if ($this->transitions->publicationBlockedReason($product) !== null) {
            return ProductDisplayState::Unavailable;
        }

        return ProductDisplayState::ApprovedHidden;
    }
}

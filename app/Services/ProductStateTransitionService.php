<?php

namespace App\Services;

use App\Enums\ProductModerationStatus;
use App\Models\Product;

class ProductStateTransitionService
{
    public function canSubmit(Product $product): bool
    {
        return in_array($product->moderation_status, [
            ProductModerationStatus::Draft,
            ProductModerationStatus::ChangesRequired,
        ], true);
    }

    public function canPublish(Product $product): bool
    {
        return ! $product->is_active
            && $product->moderation_status === ProductModerationStatus::Approved
            && $product->moderated_version === $product->moderation_version;
    }

    public function canUnpublish(Product $product): bool
    {
        return $this->isPublic($product);
    }

    public function canPushUp(Product $product): bool
    {
        return $this->isPublic($product);
    }

    public function canOpenPublicPage(Product $product): bool
    {
        return $this->isPublic($product);
    }

    public function canModerateManually(Product $product): bool
    {
        return in_array($product->moderation_status, [
            ProductModerationStatus::Pending,
            ProductModerationStatus::NeedsReview,
            ProductModerationStatus::Failed,
        ], true);
    }

    public function isPublic(Product $product): bool
    {
        return (bool) $product->is_active
            && $product->moderation_status === ProductModerationStatus::Approved
            && $product->moderated_version === $product->moderation_version
            && $product->seller?->activePackage() !== null;
    }

    public function publicationBlockedReason(Product $product): ?string
    {
        if ($product->moderation_status !== ProductModerationStatus::Approved) {
            return 'Сначала товар должен пройти модерацию.';
        }

        if ($product->moderated_version !== $product->moderation_version) {
            return 'После изменений требуется повторная модерация товара.';
        }

        return app(ProductLimitService::class)->activationError($product);
    }
}

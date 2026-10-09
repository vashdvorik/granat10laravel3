<?php

namespace App\Services;

use App\Data\ProductCabinetState;
use App\Enums\ProductModerationStatus;
use App\Models\Product;

class ProductCabinetStateService
{
    public function __construct(
        private readonly ProductStateTransitionService $transitions,
        private readonly ProductVisibilityResolver $visibilityResolver,
        private readonly ProductStatusStoryFactory $storyFactory,
    ) {}

    public function make(Product $product): ProductCabinetState
    {
        $visibility = $this->visibilityResolver->snapshot($product);
        $product->loadMissing('seller');

        $enabledBySeller = $visibility->isActive;
        $publicationRequested = (bool) $product->publication_requested;
        $moderationActual = $visibility->isModerationCurrent;
        $hasImages = $visibility->hasImages;
        $canBeOrdered = $this->canBeOrdered($product);
        $visibleToBuyers = $visibility->buyerVisible;

        [$status, $label, $description, $color, $hiddenReason, $action] = $this->state(
            product: $product,
            moderationActual: $moderationActual,
            hasImages: $hasImages,
            visibleToBuyers: $visibleToBuyers,
            sellerHidden: $visibility->sellerHidden,
        );

        return new ProductCabinetState(
            status: $status,
            label: $label,
            description: $description,
            color: $color,
            enabledBySeller: $enabledBySeller,
            publicationRequested: $publicationRequested,
            moderationActual: $moderationActual,
            hasImages: $hasImages,
            canBeOrdered: $canBeOrdered,
            visibleToBuyers: $visibleToBuyers,
            hiddenReason: $hiddenReason,
            secondaryReasons: $visibility->buyerBlockingReasons,
            primaryAction: $action,
            primaryActionLabel: $this->actionLabel($action),
        );
    }

    private function state(
        Product $product,
        bool $moderationActual,
        bool $hasImages,
        bool $visibleToBuyers,
        bool $sellerHidden,
    ): array {
        // Тексты берутся из единого источника, чтобы карточка, страница товара и уведомления говорили одно и то же
        $story = $this->storyFactory->make($product);
        $explanation = $story->explanation;
        $tone = $story->tone;
        $label = $story->headline;

        return match (true) {
            $product->moderation_status === ProductModerationStatus::Draft => ['draft', $label, $story->remark !== null ? 'Замечания прошлой проверки: '.$story->remark : $explanation, $tone, null, 'submit'],
            $product->moderation_status === ProductModerationStatus::Pending => ['queued', $label, $explanation, $tone, null, null],
            $product->moderation_status === ProductModerationStatus::Processing => ['checking', $label, $explanation, $tone, null, null],
            in_array($product->moderation_status, [ProductModerationStatus::NeedsReview, ProductModerationStatus::Failed], true)
                => ['manual_review', $label, $explanation, $tone, null, null],
            $product->moderation_status === ProductModerationStatus::ChangesRequired
                => ['changes_required', $label, $story->remark ?: $explanation, $tone, 'moderation_not_approved', 'edit'],
            $product->moderation_status === ProductModerationStatus::Rejected
                => ['rejected', $label, $story->remark ?: $explanation, $tone, 'moderation_not_approved', 'edit'],
            ! $moderationActual => ['hidden_moderation_outdated', $label, $explanation, $tone, 'moderation_outdated', 'submit'],
            ! $product->is_active => ['approved_hidden', $label, $explanation, $tone, 'not_enabled_by_seller', 'publish'],
            $sellerHidden => ['hidden_seller', $label, $explanation, $tone, 'seller_hidden', null],
            ! $hasImages => ['hidden_no_images', $label, $explanation, $tone, 'no_images', 'add_images'],
            $visibleToBuyers || $this->transitions->isPublic($product) => ['visible', $label, $explanation, $tone, null, 'push_up'],
            default => ['hidden_not_enabled', 'Снят с публикации', 'Товар сейчас не показывается покупателям.', 'gray', 'not_enabled_by_seller', 'publish'],
        };
    }
    private function canBeOrdered(Product $product): bool
    {
        return (bool) $product->can_order
            && (bool) $product->seller?->can_order;
    }

    private function actionLabel(?string $action): ?string
    {
        return match ($action) {
            'submit' => 'Отправить на проверку',
            'edit' => 'Исправить',
            'publish' => 'Опубликовать',
            'push_up' => 'Поднять товар',
            'add_images' => 'Добавить фото',
            default => null,
        };
    }
}

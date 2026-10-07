<?php

namespace App\Services;

use App\Data\ProductCabinetState;
use App\Enums\ProductModerationStatus;
use App\Models\Product;

class ProductCabinetStateService
{
    public function __construct(
        private readonly ProductStateTransitionService $transitions,
        private readonly ProductVisibilityResolver $visibilityResolver
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
    ): array {
        if ($product->moderation_status === ProductModerationStatus::Draft) {
            return ['draft', 'Черновик', 'Товар ещё не отправлен на проверку.', 'gray', null, 'submit'];
        }

        if ($product->moderation_status === ProductModerationStatus::Pending) {
            return ['queued', 'На проверке', 'Товар ожидает проверки.', 'warning', null, null];
        }

        if ($product->moderation_status === ProductModerationStatus::Processing) {
            return ['checking', 'Проверяется', 'Сейчас выполняется автоматическая проверка.', 'warning', null, null];
        }

        if (in_array($product->moderation_status, [
            ProductModerationStatus::NeedsReview,
            ProductModerationStatus::Failed,
        ], true)) {
            return ['manual_review', 'На ручной проверке', 'Товар проверяет администратор.', 'info', null, null];
        }

        if ($product->moderation_status === ProductModerationStatus::ChangesRequired) {
            return ['changes_required', 'Нужно исправить', $product->moderation_summary ?: 'Исправьте замечания и отправьте товар повторно.', 'info', 'moderation_not_approved', 'edit'];
        }

        if ($product->moderation_status === ProductModerationStatus::Rejected) {
            return ['rejected', 'Отклонён', $product->moderation_summary ?: 'Товар отклонён. Измените карточку перед повторной отправкой.', 'danger', 'moderation_not_approved', 'edit'];
        }

        if (! $moderationActual) {
            return ['hidden_moderation_outdated', 'Скрыт: нужна повторная проверка', 'Товар изменён после модерации.', 'warning', 'moderation_outdated', 'submit'];
        }

        if (! $product->is_active) {
            return ['approved_hidden', 'Одобрен, но не опубликован', 'Товар прошёл проверку, но снят с публикации.', 'gray', 'not_enabled_by_seller', 'publish'];
        }

        if (! $hasImages) {
            return ['hidden_no_images', 'Скрыт: нет фото', 'Добавьте фото, чтобы товар можно было показывать покупателям.', 'warning', 'no_images', 'add_images'];
        }

        if ($visibleToBuyers || $this->transitions->isPublic($product)) {
            return ['visible', 'Виден покупателям', 'Товар показывается в каталоге и поиске.', 'success', null, 'push_up'];
        }

        return ['hidden_not_enabled', 'Снят с публикации', 'Товар сейчас не показывается покупателям.', 'gray', 'not_enabled_by_seller', 'publish'];
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

<?php

namespace App\Services;

use App\Data\ProductViewState;
use App\Enums\ProductDisplayState;
use App\Enums\ProductModerationStatus;
use App\Enums\ProductPrimaryAction;
use App\Models\Product;

class ProductViewStateFactory
{
    public function __construct(
        private readonly ProductStateTransitionService $transitions,
        private readonly ProductDisplayStateResolver $displayStateResolver
    ) {}

    public function make(Product $product): ProductViewState
    {
        $isPublic = $this->transitions->isPublic($product);
        $canPublish = $this->transitions->canPublish($product);
        $canSubmit = $this->transitions->canSubmit($product);
        $displayState = $this->displayStateResolver->resolve($product);

        return new ProductViewState(
            displayState: $displayState,
            moderationLabel: $product->moderation_status === ProductModerationStatus::Failed
                ? 'На ручной проверке'
                : $product->moderation_status->label(),
            publicationLabel: $isPublic ? 'Опубликован' : 'Не опубликован',
            description: $this->description($product),
            color: $product->moderation_status === ProductModerationStatus::Failed
                ? 'warning'
                : $product->moderation_status->color(),
            isPublic: $isPublic,
            canSubmit: $canSubmit,
            canPublish: $canPublish,
            canUnpublish: $this->transitions->canUnpublish($product),
            canPushUp: $this->transitions->canPushUp($product),
            canOpenPublicPage: $this->transitions->canOpenPublicPage($product),
            primaryAction: match ($displayState) {
                ProductDisplayState::Draft => ProductPrimaryAction::Submit,
                ProductDisplayState::ChangesRequired,
                ProductDisplayState::Rejected => ProductPrimaryAction::Edit,
                ProductDisplayState::ApprovedHidden => ProductPrimaryAction::Publish,
                ProductDisplayState::Published => ProductPrimaryAction::PushUp,
                ProductDisplayState::Unavailable => ProductPrimaryAction::ResolveUnavailable,
                default => ProductPrimaryAction::None,
            },
            disabledReason: $displayState === ProductDisplayState::Unavailable
                ? $this->transitions->publicationBlockedReason($product)
                : null,
        );
    }

    private function description(Product $product): string
    {
        return match ($product->moderation_status) {
            ProductModerationStatus::Draft => 'Товар сохранён как черновик и ещё не отправлен на модерацию.',
            ProductModerationStatus::Pending => 'Товар ожидает автоматической проверки.',
            ProductModerationStatus::Processing => 'Сейчас выполняется автоматическая проверка товара.',
            ProductModerationStatus::NeedsReview => $product->moderation_summary
                ?: 'Товар передан администратору на проверку. Дождитесь решения.',
            ProductModerationStatus::ChangesRequired => $product->moderation_summary
                ?: 'Исправьте указанные замечания и повторно отправьте товар на проверку.',
            ProductModerationStatus::Rejected => $product->moderation_summary
                ?: 'Товар отклонён. Измените карточку перед повторной отправкой.',
            ProductModerationStatus::Failed => 'Товар передан администратору на дополнительную проверку.',
            ProductModerationStatus::Approved => $this->transitions->isPublic($product)
                ? 'Товар прошёл модерацию и виден покупателям.'
                : 'Товар одобрен. Его можно опубликовать.',
        };
    }
}

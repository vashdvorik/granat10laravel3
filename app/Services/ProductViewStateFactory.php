<?php

namespace App\Services;

use App\Data\ProductStatusStory;
use App\Data\ProductViewState;
use App\Enums\ProductDisplayState;
use App\Enums\ProductModerationStatus;
use App\Enums\ProductPrimaryAction;
use App\Models\Product;

class ProductViewStateFactory
{
    public function __construct(
        private readonly ProductStateTransitionService $transitions,
        private readonly ProductDisplayStateResolver $displayStateResolver,
        private readonly ProductStatusStoryFactory $storyFactory,
    ) {}

    public function make(Product $product): ProductViewState
    {
        $isPublic = $this->transitions->isPublic($product);
        $canPublish = $this->transitions->canPublish($product);
        $canSubmit = $this->transitions->canSubmit($product);
        $displayState = $this->displayStateResolver->resolve($product);
        $story = $this->storyFactory->make($product);

        return new ProductViewState(
            displayState: $displayState,
            moderationLabel: $product->moderation_status === ProductModerationStatus::Failed
                ? 'На ручной проверке'
                : $product->moderation_status->label(),
            publicationLabel: $isPublic ? 'Опубликован' : 'Не опубликован',
            description: $this->description($product, $story),
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
            story: $story,
        );
    }

    private function description(Product $product, ProductStatusStory $story): string
    {
        // Замечание проверяющего показываем только там, где продавец может его исправить;
        // во всех остальных состояниях — спокойное объяснение без служебных формулировок.
        return match ($product->moderation_status) {
            ProductModerationStatus::ChangesRequired,
            ProductModerationStatus::Rejected => $story->remark ?: $story->explanation,
            default => $story->explanation,
        };
    }
}

<?php

namespace App\Data;

use App\Enums\ProductDisplayState;
use App\Enums\ProductPrimaryAction;

final readonly class ProductViewState
{
    public function __construct(
        public ProductDisplayState $displayState,
        public string $moderationLabel,
        public string $publicationLabel,
        public string $description,
        public string $color,
        public bool $isPublic,
        public bool $canSubmit,
        public bool $canPublish,
        public bool $canUnpublish,
        public bool $canPushUp,
        public bool $canOpenPublicPage,
        public ProductPrimaryAction $primaryAction,
        public ?string $disabledReason = null,
        public ?ProductStatusStory $story = null,
    ) {}
}

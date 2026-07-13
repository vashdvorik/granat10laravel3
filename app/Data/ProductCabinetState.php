<?php

namespace App\Data;

final readonly class ProductCabinetState
{
    /**
     * @param  array<int, string>  $secondaryReasons
     */
    public function __construct(
        public string $status,
        public string $label,
        public string $description,
        public string $color,
        public bool $enabledBySeller,
        public bool $publicationRequested,
        public bool $moderationActual,
        public bool $hasImages,
        public bool $hasActivePackage,
        public bool $withinActiveLimit,
        public bool $withinVipLimit,
        public bool $canBeOrdered,
        public bool $visibleToBuyers,
        public ?string $hiddenReason,
        public array $secondaryReasons,
        public ?string $primaryAction,
        public ?string $primaryActionLabel,
    ) {}
}

<?php

namespace App\Data;

final readonly class ProductVisibilitySnapshot
{
    /**
     * @param  array<int, string>  $buyerBlockingReasons
     */
    public function __construct(
        public bool $publicVisible,
        public bool $buyerVisible,
        public bool $isActive,
        public bool $isModerationApproved,
        public bool $isModerationCurrent,
        public bool $hasImages,
        public bool $hasActivePackage,
        public bool $withinActiveLimit,
        public bool $withinVipLimit,
        public array $buyerBlockingReasons,
    ) {}
}

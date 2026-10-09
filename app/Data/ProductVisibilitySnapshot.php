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
        public array $buyerBlockingReasons,
        /** Магазин скрыт администратором: товар не показывается, что бы ни стояло в самом товаре. */
        public bool $sellerHidden = false,
    ) {}
}

<?php

namespace App\Data;

final readonly class PackageCompatibility
{
    /**
     * @param  array<int, string>  $reasons
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public string $status,
        public bool $isCurrent,
        public bool $canPurchaseNow,
        public bool $hasEnoughBalance,
        public bool $activeProductsFit,
        public bool $vipProductsFit,
        public bool $willDisableWebsite,
        public bool $willDisableSocialLinks,
        public bool $willDisableOrders,
        public int $activeProductsCount,
        public int $vipProductsCount,
        public ?int $activeProductsLimit,
        public ?int $vipProductsLimit,
        public int|float|string $balance,
        public int|float|string $price,
        public int $requiredActiveDeactivations,
        public int $requiredVipDeactivations,
        public array $reasons,
        public array $warnings,
        public string $action,
        public string $actionLabel,
    ) {}
}

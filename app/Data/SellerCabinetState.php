<?php

namespace App\Data;

final readonly class SellerCabinetState
{
    /**
     * @param  array<int, array{code: string, message: string, action: ?string}>  $blockers
     */
    public function __construct(
        public string $status,
        public string $label,
        public string $description,
        public string $alertType,
        public ?string $primaryAction,
        public ?string $primaryActionLabel,
        public array $blockers,
        public bool $hasActivePackage,
        public ?string $packageName,
        public ?\DateTimeInterface $packageEndsAt,
        public ?int $packageDaysLeft,
        public bool $packageIsLifetime,
        public int|float|string|null $packageRenewalPrice,
        public bool $autoRenewEnabled,
        public bool $autoRenewPossible,
        public int|float|string $balance,
        public int $activeProductsCount,
        public int $visibleProductsCount,
        public ?int $activeProductsLimit,
        public int $vipProductsCount,
        public ?int $vipProductsLimit,
        public bool $canShowWebsite,
        public bool $canShowSocialLinks,
        public bool $canAcceptOrders,
    ) {}

    public function primaryBlocker(): ?array
    {
        return $this->blockers[0] ?? null;
    }
}

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
        public int|float|string $balance,
        public int $activeProductsCount,
        public int $visibleProductsCount,
        public int $vipProductsCount,
        public bool $canAcceptOrders,
    ) {}

    public function primaryBlocker(): ?array
    {
        return $this->blockers[0] ?? null;
    }
}

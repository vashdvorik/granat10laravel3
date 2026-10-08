<?php

namespace App\Data;

use Illuminate\Support\Carbon;

/**
 * Всё, что нужно интерфейсу, чтобы объяснить продавцу про VIP товара: что сейчас, что можно сделать и почему нельзя.
 */
final readonly class VipState
{
    public const INACTIVE = 'inactive';
    public const ACTIVE = 'active';
    public const PERMANENT = 'permanent'; // выдан администратором без срока

    /**
     * @param  array<int, array{code: string, message: string}>  $blockers  причины, по которым VIP купить нельзя
     * @param  array<int, string>  $warnings  важные предупреждения (не запрещают покупку)
     * @param  array<int, array{days: int, label: string, price_cents: int, per_day_cents: int, new_until: Carbon, affordable: bool, shortfall_cents: int}>  $options
     */
    public function __construct(
        public string $status,
        public ?Carbon $until,
        public ?int $daysLeft,
        public bool $eligible,
        public array $blockers,
        public array $warnings,
        public int $balanceCents,
        public int $pricePerDayCents,
        public array $options,
        public bool $canCancel,
        public int $refundCents,
        public ?string $cancelBlockedReason,
        public bool $expiresSoon,
    ) {}

    public function isActive(): bool
    {
        return $this->status !== self::INACTIVE;
    }

    public function isPermanent(): bool
    {
        return $this->status === self::PERMANENT;
    }

    /** Можно ли сейчас купить или продлить VIP. */
    public function canBuy(): bool
    {
        return $this->eligible && ! $this->isPermanent();
    }

    /** Самая выгодная для объяснения сумма пополнения: сколько не хватает на самый короткий срок. */
    public function cheapestShortfallCents(): int
    {
        return $this->options === [] ? 0 : min(array_column($this->options, 'shortfall_cents'));
    }
}

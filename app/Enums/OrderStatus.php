<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Accepted = 'accepted';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Accepted => 'Принят',
            self::Processing => 'В работе',
            self::ReadyForPickup => 'Готов к самовывозу',
            self::OutForDelivery => 'Передан в доставку',
            self::Completed => 'Выполнен',
            self::Cancelled => 'Отменён',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Accepted, self::Cancelled],
            self::Accepted => [self::Processing, self::Cancelled],
            self::Processing => [
                self::ReadyForPickup,
                self::OutForDelivery,
                self::Completed,
                self::Cancelled,
            ],
            self::ReadyForPickup, self::OutForDelivery => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}

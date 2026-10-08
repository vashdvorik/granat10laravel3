<?php

namespace App\Enums;

/** Тип операции в журнале баланса. */
enum BalanceTransactionType: string
{
    case Topup = 'topup';
    case VipPurchase = 'vip_purchase';
    case VipRefund = 'vip_refund';
    case TipSent = 'tip_sent';
    case TipReceived = 'tip_received';
    case AdminAdjustment = 'admin_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Topup => 'Пополнение картой',
            self::VipPurchase => 'Покупка VIP',
            self::VipRefund => 'Возврат за VIP',
            self::TipSent => 'Благодарность разработчику',
            self::TipReceived => 'Получена благодарность',
            self::AdminAdjustment => 'Корректировка администратором',
        };
    }

    /** Иконка Unicons (кабинет). */
    public function icon(): string
    {
        return match ($this) {
            self::Topup => 'uil-credit-card',
            self::VipPurchase => 'uil-star',
            self::VipRefund => 'uil-corner-up-left',
            self::TipSent => 'uil-heart',
            self::TipReceived => 'uil-gift',
            self::AdminAdjustment => 'uil-setting',
        };
    }

    /** Цвет для Filament-бейджа. */
    public function color(): string
    {
        return match ($this) {
            self::Topup, self::VipRefund, self::TipReceived => 'success',
            self::VipPurchase => 'warning',
            self::TipSent => 'info',
            self::AdminAdjustment => 'gray',
        };
    }

    /** @return array<string, string> значение => название, для фильтров */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

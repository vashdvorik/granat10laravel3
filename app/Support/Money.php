<?php

namespace App\Support;

/** Деньги в копейках: форматирование для интерфейса. */
final class Money
{
    /** 123456 → «1 234,56» */
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ');
    }

    /** Десятичная сумма из БД («12.50», 12.5, null) → копейки. */
    public static function fromDecimal(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /** Админка везде показывает суммы в RUP: 123456 → «1 234,56 RUP»; −500 → «−5,00 RUP» */
    public static function rup(int $cents, bool $withSign = false): string
    {
        $sign = $cents < 0 ? '−' : ($withSign && $cents > 0 ? '+' : '');

        return $sign.self::format(abs($cents)).' RUP';
    }

    /** 123456 → «1 234,56 ₽»; −500 → «−5,00 ₽» */
    public static function rubles(int $cents): string
    {
        return ($cents < 0 ? '−' : '').self::format(abs($cents)).' ₽';
    }
}

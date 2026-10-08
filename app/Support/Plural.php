<?php

namespace App\Support;

/** Склонение по числу для русского языка: 1 день, 2 дня, 5 дней, 21 день. */
final class Plural
{
    public static function ru(int $number, string $one, string $few, string $many): string
    {
        $n = abs($number) % 100;
        $last = $n % 10;

        if ($n > 10 && $n < 20) {
            return $many;
        }

        if ($last === 1) {
            return $one;
        }

        return $last >= 2 && $last <= 4 ? $few : $many;
    }

    /** «5 дней» */
    public static function days(int $number): string
    {
        return $number.' '.self::ru($number, 'день', 'дня', 'дней');
    }
}

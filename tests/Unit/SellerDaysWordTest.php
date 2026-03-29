<?php

namespace Tests\Unit;

use App\Models\Seller;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тест: склонение слова "день" в Seller::getDaysWord().
 * Не требует БД — тестирует чистую PHP-логику.
 *
 * @covers \App\Models\Seller
 */
class SellerDaysWordTest extends TestCase
{
    private Seller $seller;

    protected function setUp(): void
    {
        parent::setUp();
        // Создаём объект без персистентности — только логика
        $this->seller = new Seller();
    }

    /** @dataProvider daysWordProvider */
    public function test_days_word_declension(int $days, string $expected): void
    {
        $result = $this->callProtected($days);
        $this->assertSame($expected, $result);
    }

    public static function daysWordProvider(): array
    {
        return [
            [1,   'день'],
            [2,   'дня'],
            [3,   'дня'],
            [4,   'дня'],
            [5,   'дней'],
            [6,   'дней'],
            [10,  'дней'],
            [11,  'дней'],  // исключение: 11 всегда "дней"
            [12,  'дней'],  // исключение: 12 всегда "дней"
            [13,  'дней'],  // исключение
            [14,  'дней'],  // исключение
            [21,  'день'],
            [22,  'дня'],
            [24,  'дня'],
            [25,  'дней'],
            [100, 'дней'],
            [101, 'день'],
            [111, 'дней'],  // исключение: 11
            [0,   'дней'],
        ];
    }

    /** Вызов protected-метода через Reflection */
    private function callProtected(int $days): string
    {
        $reflection = new \ReflectionMethod(Seller::class, 'getDaysWord');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->seller, $days);
    }
}

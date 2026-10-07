<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class VipPricing
{
    /**
     * Стоимость одного дня VIP в копейках.
     */
    public function pricePerDayInCents(): int
    {
        $cached = Cache::get(config('vip.cache_key'));

        if (is_int($cached)) {
            return $cached;
        }

        $cents = $this->defaultInCents();

        try {
            if (Schema::hasTable('site_settings')) {
                $stored = SiteSetting::query()
                    ->where('key', config('vip.setting_key'))
                    ->value('value');

                if (is_string($stored) && preg_match('/^\d+$/', $stored)) {
                    $cents = (int) $stored;
                }
            }
        } catch (Throwable) {
            // используем значение по умолчанию
        }

        Cache::forever(config('vip.cache_key'), $cents);

        return $cents;
    }

    public function pricePerDay(): string
    {
        return $this->format($this->pricePerDayInCents());
    }

    public function setPricePerDay(int|float|string $price): void
    {
        $normalized = str_replace(',', '.', trim((string) $price));

        if (! preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Стоимость дня VIP должна быть неотрицательным числом с точностью до копеек.');
        }

        $cents = (int) round(((float) $normalized) * 100);

        SiteSetting::query()->updateOrCreate(
            ['key' => config('vip.setting_key')],
            ['value' => (string) $cents],
        );

        Cache::forever(config('vip.cache_key'), $cents);
    }

    /**
     * @return array<int, string> дни => название
     */
    public function durations(): array
    {
        return config('vip.durations', []);
    }

    public function isValidDuration(int $days): bool
    {
        return array_key_exists($days, $this->durations());
    }

    public function priceInCents(int $days): int
    {
        return $this->pricePerDayInCents() * $days;
    }

    public function price(int $days): string
    {
        return $this->format($this->priceInCents($days));
    }

    /**
     * @return array<int, array{days: int, label: string, price: string}>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->durations() as $days => $label) {
            $options[] = [
                'days' => (int) $days,
                'label' => $label,
                'price' => $this->price((int) $days),
            ];
        }

        return $options;
    }

    public function format(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function defaultInCents(): int
    {
        return (int) round(((float) config('vip.default_price_per_day', 1)) * 100);
    }
}

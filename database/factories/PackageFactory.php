<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name'                   => 'Пакет ' . ucfirst(fake()->word()),
            'price'                  => fake()->randomFloat(2, 0, 5000),
            'duration_days'          => fake()->randomElement([30, 60, 90, null]),
            'active_products_limit'  => 10,
            'vip_products_limit'     => 3,
            'has_website_link'       => false,
            'has_social_links'       => false,
            'has_order_system'       => false,
            'is_active'              => true,
        ];
    }

    /** Задать конкретные лимиты товаров */
    public function withLimits(int $products, int $vip): static
    {
        return $this->state([
            'active_products_limit' => $products,
            'vip_products_limit'    => $vip,
        ]);
    }

    /** Бесплатный бессрочный пакет */
    public function free(): static
    {
        return $this->state([
            'price'         => 0,
            'duration_days' => null,
        ]);
    }

    /** Платный на месяц */
    public function monthly(): static
    {
        return $this->state([
            'price'         => fake()->randomFloat(2, 500, 3000),
            'duration_days' => 30,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name'            => ucfirst($name),
            'price'           => fake()->randomFloat(2, 100, 100_000),
            'lowprice'        => null,
            'desc'            => fake()->paragraph(),
            'features1'       => fake()->sentence(5),
            'features2'       => null,
            'features3'       => null,
            'features4'       => null,
            'alias'           => Str::slug($name) . '-' . fake()->unique()->randomNumber(5),
            'is_vip'          => false,
            'is_active'       => true,
            'category_id'     => Category::factory(),
            'seller_id'       => Seller::factory(),
            'sale_started_at' => null,
            'push_at'         => now(),
        ];
    }

    /** Неактивный товар */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** VIP-товар */
    public function vip(): static
    {
        return $this->state(['is_vip' => true]);
    }

    /** Активный VIP-товар */
    public function activeVip(): static
    {
        return $this->state(['is_active' => true, 'is_vip' => true]);
    }

    /** Товар со скидкой */
    public function onSale(): static
    {
        return $this->state([
            'lowprice'        => fake()->randomFloat(2, 50, 50_000),
            'sale_started_at' => now(),
        ]);
    }
}

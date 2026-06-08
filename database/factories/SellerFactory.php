<?php

namespace Database\Factories;

use App\Models\Seller;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
{
    protected $model = Seller::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id'      => User::factory(),
            'name'         => $name,
            'phone'        => fake()->phoneNumber(),
            'town'         => fake()->city(),
            'settlement_id' => Settlement::factory(),
            'sellerplace'  => fake()->streetName(),
            'address'      => fake()->address(),
            'description'  => fake()->paragraph(),
            'alias'        => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'product_count' => 0,
        ];
    }
}

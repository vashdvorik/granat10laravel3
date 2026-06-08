<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'region_id' => Region::factory(),
            'name' => $name,
            'type' => 'город',
            'zip' => 'MD-'.fake()->numerify('####'),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

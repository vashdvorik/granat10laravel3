<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'type' => 'Район',
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

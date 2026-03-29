<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path'       => 'products/' . fake()->uuid() . '.jpg',
            'is_main'    => false,
        ];
    }

    /** Главное изображение товара */
    public function main(): static
    {
        return $this->state(['is_main' => true]);
    }
}

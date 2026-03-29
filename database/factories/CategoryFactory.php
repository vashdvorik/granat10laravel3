<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'      => ucfirst($name),
            'alias'     => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'number'    => fake()->numberBetween(1, 99),
            'desc'      => fake()->sentence(),
            'parent_id' => null,
            'is_active' => true,
        ];
    }

    /** Неактивная категория */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Дочерняя категория */
    public function childOf(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}

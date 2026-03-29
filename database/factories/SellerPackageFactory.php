<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerPackage>
 */
class SellerPackageFactory extends Factory
{
    protected $model = SellerPackage::class;

    public function definition(): array
    {
        return [
            'seller_id'  => Seller::factory(),
            'package_id' => Package::factory(),
            'start_date' => now()->toDateString(),
            'end_date'   => null,  // бессрочный по умолчанию
            'is_active'  => true,
        ];
    }

    /** Просроченный пакет */
    public function expired(): static
    {
        return $this->state([
            'end_date'  => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);
    }

    /** Деактивированный пакет */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Пакет, истекающий через N дней */
    public function expiresInDays(int $days): static
    {
        return $this->state([
            'end_date' => now()->addDays($days)->toDateString(),
        ]);
    }

    /** Активный срочный пакет (не истёк) */
    public function active(): static
    {
        return $this->state([
            'is_active' => true,
            'end_date'  => now()->addDays(30)->toDateString(),
        ]);
    }
}

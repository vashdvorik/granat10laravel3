<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('packages')->delete();

        DB::table('packages')->insert([
            [
                'name' => 'Базовый',
                'price' => 0,
                'duration_days' => null,
                'active_products_limit' => 10,
                'vip_products_limit' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Премиум',
                'price' => 250,
                'duration_days' => 30,
                'active_products_limit' => 100,
                'vip_products_limit' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

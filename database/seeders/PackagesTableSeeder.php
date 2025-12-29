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
            // 1. Старт (бесплатный)
            [
                'name' => 'Старт',
                'price' => 0,
                'duration_days' => null, // бессрочный
                'active_products_limit' => 15,
                'vip_products_limit' => 0,
                'has_website_link' => false,
                'has_social_links' => false,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 2. Базовый
            [
                'name' => 'Базовый',
                'price' => 160,
                'duration_days' => 30,
                'active_products_limit' => 50,
                'vip_products_limit' => 1,
                'has_website_link' => false,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 3. Лайт (популярный)
            [
                'name' => 'Лайт',
                'price' => 520,
                'duration_days' => 30,
                'active_products_limit' => 100,
                'vip_products_limit' => 3,
                'has_website_link' => true,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 4. Смарт (премиум)
            [
                'name' => 'Смарт',
                'price' => 2900,
                'duration_days' => 30,
                'active_products_limit' => null, // безлимит
                'vip_products_limit' => 50,
                'has_website_link' => true,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
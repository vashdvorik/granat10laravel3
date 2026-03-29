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
                'price' => 25,
                'duration_days' => 30,
                'active_products_limit' => 50,
                'vip_products_limit' => 0,
                'has_website_link' => false,
                'has_social_links' => false,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // 3. Лайт (популярный)
            [
                'name' => 'Лайт',
                'price' => 150,
                'duration_days' => 30,
                'active_products_limit' => 100,
                'vip_products_limit' => 1,
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
                'price' => 450,
                'duration_days' => 30,
                'active_products_limit' => 300, // безлимит
                'vip_products_limit' => 5,
                'has_website_link' => true,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 5. БИЗНЕС
            [
                'name' => 'Бизнес',
                'price' => 1500,
                'duration_days' => 30,
                'active_products_limit' => 500, 
                'vip_products_limit' => 10,
                'has_website_link' => true,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 6. МАГАЗИН
            [
                'name' => 'Магазин',
                'price' => 3000,
                'duration_days' => 30,
                'active_products_limit' => 1000, 
                'vip_products_limit' => 100,
                'has_website_link' => true,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 7. ЭЛИТ
            [
                'name' => 'Элит',
                'price' => 8000,
                'duration_days' => 30,
                'active_products_limit' => 5000, 
                'vip_products_limit' => 500,
                'has_website_link' => true,
                'has_social_links' => true,
                'has_order_system' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // 8. ПРЕМИУМ
            [
                'name' => 'Премиум',
                'price' => 15000,
                'duration_days' => 30,
                'active_products_limit' => 10000, 
                'vip_products_limit' => 1000,
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
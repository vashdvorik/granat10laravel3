<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('images')->delete();

        DB::table('images')->insert([
            [
                'product_id' => 1,
                'path' => 'products/1.jpg',
                'alias' => 'tshirt-white-main',
                'alt' => 'Футболка белая',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 1,
                'path' => 'products/2.jpg',
                'alias' => 'tshirt-white-main',
                'alt' => 'Футболка белая',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 1,
                'path' => 'products/3.jpg',
                'alias' => 'tshirt-white-main',
                'alt' => 'Футболка белая',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 1,
                'path' => 'products/4.jpg',
                'alias' => 'tshirt-white-main',
                'alt' => 'Футболка белая',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 1,
                'path' => 'products/5.jpg',
                'alias' => 'tshirt-white-main',
                'alt' => 'Футболка белая',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 1,
                'path' => 'products/6.jpg',
                'alias' => 'tshirt-white-main',
                'alt' => 'Футболка белая',
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => 2,
                'path' => 'products/2.jpg',
                'alias' => 'ankle-boots-women-main',
                'alt' => 'Ботильоны женские',
                'order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

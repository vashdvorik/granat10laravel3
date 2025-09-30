<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->delete();

        // 1 уровень
        $clothesId = DB::table('categories')->insertGetId([
            'name' => 'Одежда',
            'alias' => 'clothes',
            'number' => 1,
            'desc' => 'Вся одежда',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $shoesId = DB::table('categories')->insertGetId([
            'name' => 'Обувь',
            'alias' => 'shoes',
            'number' => 2,
            'desc' => 'Вся обувь',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $accessoriesId = DB::table('categories')->insertGetId([
            'name' => 'Аксессуары',
            'alias' => 'accessories',
            'number' => 7,
            'desc' => 'Все аксессуары',
            'parent_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2 уровень
        $menClothesId = DB::table('categories')->insertGetId([
            'name' => 'Мужская одежда',
            'alias' => 'mens-clothes',
            'number' => 3,
            'desc' => 'Одежда для мужчин',
            'parent_id' => $clothesId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $womenShoesId = DB::table('categories')->insertGetId([
            'name' => 'Женская обувь',
            'alias' => 'womens-shoes',
            'number' => 4,
            'desc' => 'Обувь для женщин',
            'parent_id' => $shoesId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3 уровень
        DB::table('categories')->insert([
            [
                'name' => 'Футболки',
                'alias' => 'tshirts',
                'number' => 5,
                'desc' => 'Футболки для мужчин',
                'parent_id' => $menClothesId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ботильоны',
                'alias' => 'ankle-boots',
                'number' => 6,
                'desc' => 'Женские ботильоны',
                'parent_id' => $womenShoesId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

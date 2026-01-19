<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->delete();

        DB::table('products')->insert([
            [
                'name' => 'Футболка белая',
                'category_id' => 8, // Футболки
                'seller_id' => 1,
                'price' => 799,
                'lowprice' => null,
                'desc' => 'Классическая белая футболка из 100% натурального хлопка — базовый элемент гардероба на каждый день. Мягкий и приятный к телу материал обеспечивает комфорт в течение всего дня. Прямой крой подходит для любого типа фигуры, а лаконичный дизайн легко сочетается с джинсами, брюками или шортами. Отличный выбор для повседневной носки, спорта или в качестве нижнего слоя под рубашку или свитер.',
                'features1' => '100% хлопок',
                'features2' => 'Белый цвет',
                'features3' => 'Размеры S-XL',
                'features4' => 'Доставка Тирасполь',
                'alias' => 'tshirt-white',
                'is_vip' => false,
                'is_active' => true,
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Футболка синяя',
                'category_id' => 8, // Футболки
                'seller_id' => 1,
                'price' => 899,
                'lowprice' => 799,
                'desc' => 'Стильная синяя футболка из хлопка.',
                'features1' => '100% хлопок',
                'features2' => 'Синий цвет',
                'features3' => 'Размеры S-XL',
                'features4' => 'Доставка по Тирасполю',
                'alias' => 'tshirt-blue',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Футболка с принтом',
                'category_id' => 8, // Футболки
                'seller_id' => 2,
                'price' => 999,
                'lowprice' => null,
                'desc' => 'Футболка с ярким принтом.',
                'features1' => '100% хлопок',
                'features2' => 'Принт',
                'features3' => null,
                'features4' => null,
                'alias' => 'tshirt-print',
                'is_vip' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Футболка черная',
                'category_id' => 8, // Футболки
                'seller_id' => 1,
                'price' => 799,
                'lowprice' => 699,
                'desc' => 'Классическая черная футболка.',
                'features1' => '100% хлопок',
                'features2' => 'Черный цвет',
                'features3' => 'Размеры S-XL',
                'features4' => null,
                'alias' => 'tshirt-black',
                'is_vip' => false,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Ботильоны женские',
                'category_id' => 27, // Ботильоны
                'seller_id' => 2,
                'price' => 4999,
                'lowprice' => 3999,
                'desc' => 'Модные женские ботильоны.',
                'features1' => 'Натуральная кожа',
                'features2' => 'Черный цвет',
                'features3' => 'Размеры 36-41',
                'features4' => 'Черный цвет',
                'alias' => 'ankle-boots-women',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Ботильоны замшевые',
                'category_id' => 27, // Ботильоны
                'seller_id' => 2,
                'price' => 5499,
                'lowprice' => null,
                'desc' => 'Замшевые ботильоны на каблуке.',
                'features1' => 'Натуральная замша',
                'features2' => 'Бежевый цвет',
                'features3' => 'Размеры 36-40',
                'features4' => null,
                'alias' => 'ankle-boots-suede',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Ботильоны демисезонные',
                'category_id' => 27, // Ботильоны
                'seller_id' => 1,
                'price' => 3999,
                'lowprice' => null,
                'desc' => 'Демисезонные ботильоны для женщин.',
                'features1' => 'Искусственная кожа',
                'features2' => 'Коричневый цвет',
                'features3' => 'Размеры 37-41',
                'features4' => 'Размеры 37-41',
                'alias' => 'ankle-boots-demi',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Ботильоны на платформе',
                'category_id' => 27, // Ботильоны
                'seller_id' => 2,
                'price' => 5999,
                'lowprice' => 4999,
                'desc' => 'Ботильоны на высокой платформе.',
                'features1' => 'Натуральная кожа',
                'features2' => 'Красный цвет',
                'features3' => null,
                'features4' => null,
                'alias' => 'ankle-boots-platform',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
        ]);
    }
}

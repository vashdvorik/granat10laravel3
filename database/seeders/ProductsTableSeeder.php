<?php

namespace Database\Seeders;

use App\Enums\ProductModerationStatus;
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

        $seller1Id = DB::table('sellers')->where('alias', 'moda')->value('id');
        $seller2Id = DB::table('sellers')->where('alias', 'technika')->value('id');

        if (!$seller1Id || !$seller2Id) {
            $this->command->warn('ProductsTableSeeder: продавцы не найдены, пропускаем.');
            return;
        }

        $products = [
            [
                'name' => 'Красивое платье(пример)',
                'category_id' => 1112, // платья
                'seller_id' => $seller1Id,
                'price' => 799,
                'lowprice' => 699,
                'desc' => 'Классическое белое платье из 100% натурального хлопка — базовый элемент гардероба на каждый день. Мягкий и приятный к телу материал обеспечивает комфорт в течение всего дня. Прямой крой подходит для любого типа фигуры, а лаконичный дизайн легко сочетается с джинсами, брюками или шортами. Отличный выбор для повседневной носки, спорта или в качестве нижнего слоя под рубашку или свитер.',
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
                'name' => 'Игровой ноутбук(пример)',
                'category_id' => 10201, // ноутбуки
                'seller_id' => $seller1Id,
                'price' => 899,
                'lowprice' => 799,
                'desc' => 'Мощный игровой ноутбук с высокой производительностью и современным дизайном.',
                'features1' => 'Процессор Intel i7',
                'features2' => '16GB RAM',
                'features3' => '512GB SSD',
                'features4' => 'Доставка по Тирасполю',
                'alias' => 'gaming-laptop',
                'is_vip' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Холодильник(пример)',
                'category_id' => 11101, // холодильники
                'seller_id' => $seller2Id,
                'price' => 999,
                'lowprice' => null,
                'desc' => 'Мощный и энергоэффективный холодильник с современным дизайном.',
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
                'name' => 'Офисное кресло(пример)',
                'category_id' => 12106, // офисная мебель
                'seller_id' => $seller1Id,
                'price' => 799,
                'lowprice' => 699,
                'desc' => 'Классическое черное офисное кресло.',
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
                'name' => 'Смеситель для ванной(пример)',
                'category_id' => 13204, // Смесители
                'seller_id' => $seller2Id,
                'price' => 4999,
                'lowprice' => 3999,
                'desc' => 'Модный и функциональный смеситель для ванной.',
                'features1' => 'Нержавеющая сталь',
                'features2' => 'Хромированное покрытие',
                'features3' => 'Современный дизайн',
                'features4' => 'Легкая установка',
                'alias' => 'bathroom-faucet',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'парфюмерная вода(пример)',
                'category_id' => 14203, // Унисекс ароматы
                'seller_id' => $seller2Id,
                'price' => 5499,
                'lowprice' => null,
                'desc' => 'Универсальный аромат для мужчин и женщин.',
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
                'name' => 'Коляска прогулочная(пример)',
                'category_id' => 15301, // Коляски прогулочные
                'seller_id' => $seller1Id,
                'price' => 3999,
                'lowprice' => null,
                'desc' => 'Прогулочная коляска для детей.',

                'features1' => 'Искусственная кожа',
                'features2' => 'Коричневый цвет',
                'features3' => 'Размеры 37-41',
                'features4' => 'Размеры 37-41',
                'alias' => 'stroller-walking',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Электросамокат(пример)',
                'category_id' => 18305, // Электросамокаты
                'seller_id' => $seller1Id,
                'price' => 5999,
                'lowprice' => 4999,
                'desc' => 'Электросамокат для взрослых и детей.',
                'features1' => 'Натуральная кожа',
                'features2' => 'Красный цвет',
                'features3' => null,
                'features4' => null,
                'alias' => 'electric-scooter',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
            [
                'name' => 'Держатель для телефона(пример)',
                'category_id' => 20405, // Держатели для телефона
                'seller_id' => $seller1Id,
                'price' => 5999,
                'lowprice' => 4999,
                'desc' => 'Держатель для телефона для автомобилей.',
                'features1' => 'Натуральная кожа',
                'features2' => 'Красный цвет',
                'features3' => null,
                'features4' => null,
                'alias' => 'phone-holder',
                'is_vip' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'push_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
                'sale_started_at' => Carbon::now()->subDays(rand(0, 15))->subHours(rand(0, 23)),
            ],
        ];

        $moderatedAt = now();

        DB::table('products')->insert(array_map(
            fn (array $product): array => array_merge($product, [
                'moderation_status' => ProductModerationStatus::Approved->value,
                'moderation_version' => 1,
                'moderated_version' => 1,
                'moderation_source' => 'seeder',
                'moderated_at' => $moderatedAt,
            ]),
            $products
        ));
    }
}

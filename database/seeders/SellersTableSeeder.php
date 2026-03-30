<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SellersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sellers')->delete();

        $seller1UserId = DB::table('users')->where('email', 'seller1@example.com')->value('id');
        $seller2UserId = DB::table('users')->where('email', 'seller2@example.com')->value('id');

        if (!$seller1UserId || !$seller2UserId) {
            return;
        }

        DB::table('sellers')->insert([
            [
                'user_id' => $seller1UserId,
                'name' => '"Тестовый магазин Мода"',
                'phone' => '(779)93-603',
                'town' => 'Тирасполь',
                'address' => 'ул. Примерная, 1',
                'description' => '"Лучший магазин одежды для всей семьи! У нас вы найдете стильные и качественные вещи на любой сезон - от повседневных образов до нарядных луков. Широкий размерный ряд, доступные цены и постоянные новинки. Удобная примерка, быстрая доставка и выгодные акции. Создавайте идеальные образы с нами!" ',
                'link' => 'https://moda.ru',
                'link_map' => 'https://maps.app.goo.gl/nCfZEwZTQBFfK2cMA',
                'telegram' => '@moda',
                'instagram' => '@moda_insta',
                'viber' => '+37377993603',
                'photos' => null,
                'photo' => null,
                'alias' => 'moda',
                'sellerplace' => 'Центральный район',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $seller2UserId,
                'name' => '"Тестовый магазин Техника"',
                'phone' => '(779)93-604',
                'town' => 'Тирасполь',
                'address' => 'ул. Примерная, 2',
                'description' => 'Магазин бытовой техники и электроники.',
                'link' => null,
                'link_map' => null,
                'telegram' => '@technika',
                'instagram' => null,
                'viber' => null,
                'photos' => null,
                'photo' => null,
                'alias' => 'technika',
                'sellerplace' => 'Северный район',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}


<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Settlement;
use Illuminate\Database\Seeder;

class RegionsAndSettlementsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->regions() as $regionOrder => $regionData) {
            $region = Region::query()->updateOrCreate(
                ['slug' => $regionData['slug']],
                [
                    'name' => $regionData['name'],
                    'type' => $regionData['type'],
                    'is_active' => true,
                    'sort_order' => $regionOrder,
                ]
            );

            foreach ($regionData['settlements'] as $settlementOrder => $settlementData) {
                Settlement::query()->updateOrCreate(
                    ['slug' => $settlementData['slug']],
                    [
                        'region_id' => $region->id,
                        'name' => $settlementData['name'],
                        'type' => $settlementData['type'],
                        'zip' => $settlementData['zip'],
                        'is_active' => true,
                        'sort_order' => $settlementOrder,
                    ]
                );
            }
        }
    }

    private function regions(): array
    {
        return [
            [
                'name' => 'Тирасполь',
                'type' => 'Город республиканского подчинения',
                'slug' => 'tiraspol',
                'settlements' => [
                    ['name' => 'Тирасполь', 'type' => 'город', 'zip' => 'MD-3300', 'slug' => 'tiraspol'],
                    ['name' => 'Новотираспольский', 'type' => 'пгт', 'zip' => 'MD-3300', 'slug' => 'novotiraspolskiy'],
                ],
            ],
            [
                'name' => 'Бендеры',
                'type' => 'Город республиканского подчинения',
                'slug' => 'bendery',
                'settlements' => [
                    ['name' => 'Бендеры', 'type' => 'город', 'zip' => 'MD-3200', 'slug' => 'bendery'],
                    ['name' => 'Гиска', 'type' => 'село', 'zip' => 'MD-3200', 'slug' => 'giska'],
                    ['name' => 'Протягайловка', 'type' => 'село', 'zip' => 'MD-3200', 'slug' => 'protyagaylovka'],
                ],
            ],
            [
                'name' => 'Слободзейский район',
                'type' => 'Район',
                'slug' => 'slobodzeya-district',
                'settlements' => [
                    ['name' => 'Слободзея', 'type' => 'город', 'zip' => 'MD-5700', 'slug' => 'slobodzeya'],
                    ['name' => 'Днестровск', 'type' => 'город', 'zip' => 'MD-3352', 'slug' => 'dnestrovsk'],
                    ['name' => 'Первомайск', 'type' => 'пгт', 'zip' => 'MD-5714', 'slug' => 'pervomaysk'],
                    ['name' => 'Красное', 'type' => 'пгт', 'zip' => 'MD-5711', 'slug' => 'krasnoe'],
                    ['name' => 'Парканы', 'type' => 'село', 'zip' => 'MD-5715', 'slug' => 'parkany'],
                    ['name' => 'Суклея', 'type' => 'село', 'zip' => 'MD-5718', 'slug' => 'sukleya'],
                    ['name' => 'Кицканы', 'type' => 'село', 'zip' => 'MD-5716', 'slug' => 'kitskany'],
                    ['name' => 'Терновка', 'type' => 'село', 'zip' => 'MD-5719', 'slug' => 'ternovka'],
                    ['name' => 'Ближний Хутор', 'type' => 'село', 'zip' => 'MD-5712', 'slug' => 'blizhniy-khutor'],
                    ['name' => 'Чобручи', 'type' => 'село', 'zip' => 'MD-5720', 'slug' => 'chobruchi'],
                    ['name' => 'Карагаш', 'type' => 'село', 'zip' => 'MD-5713', 'slug' => 'karagash'],
                    ['name' => 'Глиное', 'type' => 'село', 'zip' => 'MD-5717', 'slug' => 'glinoe'],
                    ['name' => 'Коротное', 'type' => 'село', 'zip' => 'MD-5721', 'slug' => 'korotnoe'],
                ],
            ],
            [
                'name' => 'Рыбницкий район',
                'type' => 'Район',
                'slug' => 'rybnitsa-district',
                'settlements' => [
                    ['name' => 'Рыбница', 'type' => 'город', 'zip' => 'MD-5500', 'slug' => 'rybnitsa'],
                    ['name' => 'Ержово', 'type' => 'село', 'zip' => 'MD-5517', 'slug' => 'erzhovo'],
                    ['name' => 'Колбасна', 'type' => 'село', 'zip' => 'MD-5519', 'slug' => 'kolbasna'],
                    ['name' => 'Попенки', 'type' => 'село', 'zip' => 'MD-5529', 'slug' => 'popenki'],
                    ['name' => 'Большой Молокиш', 'type' => 'село', 'zip' => 'MD-5511', 'slug' => 'bolshoy-molokish'],
                ],
            ],
            [
                'name' => 'Дубоссарский район',
                'type' => 'Район',
                'slug' => 'dubossary-district',
                'settlements' => [
                    ['name' => 'Дубоссары', 'type' => 'город', 'zip' => 'MD-4500', 'slug' => 'dubossary'],
                    ['name' => 'Лунга', 'type' => 'село', 'zip' => 'MD-4500', 'slug' => 'lunga'],
                    ['name' => 'Дзержинское', 'type' => 'село', 'zip' => 'MD-4511', 'slug' => 'dzerzhinskoe'],
                    ['name' => 'Дойбаны-1', 'type' => 'село', 'zip' => 'MD-4513', 'slug' => 'doybany-1'],
                    ['name' => 'Дойбаны-2', 'type' => 'село', 'zip' => 'MD-4513', 'slug' => 'doybany-2'],
                ],
            ],
            [
                'name' => 'Григориопольский район',
                'type' => 'Район',
                'slug' => 'grigoriopol-district',
                'settlements' => [
                    ['name' => 'Григориополь', 'type' => 'город', 'zip' => 'MD-4000', 'slug' => 'grigoriopol'],
                    ['name' => 'Маяк', 'type' => 'пгт', 'zip' => 'MD-4011', 'slug' => 'mayak'],
                    ['name' => 'Малаешты', 'type' => 'село', 'zip' => 'MD-4021', 'slug' => 'malaeshty'],
                    ['name' => 'Бутор', 'type' => 'село', 'zip' => 'MD-4014', 'slug' => 'butor'],
                    ['name' => 'Ташлык', 'type' => 'село', 'zip' => 'MD-4027', 'slug' => 'tashlyk'],
                    ['name' => 'Спея', 'type' => 'село', 'zip' => 'MD-4026', 'slug' => 'speya'],
                    ['name' => 'Делакеу', 'type' => 'село', 'zip' => 'MD-4017', 'slug' => 'delakeu'],
                ],
            ],
            [
                'name' => 'Каменский район',
                'type' => 'Район',
                'slug' => 'kamenka-district',
                'settlements' => [
                    ['name' => 'Каменка', 'type' => 'город', 'zip' => 'MD-6600', 'slug' => 'kamenka'],
                    ['name' => 'Подойма', 'type' => 'село', 'zip' => 'MD-6624', 'slug' => 'podoyma'],
                    ['name' => 'Подоймица', 'type' => 'село', 'zip' => 'MD-6624', 'slug' => 'podoymitsa'],
                    ['name' => 'Рашков', 'type' => 'село', 'zip' => 'MD-6625', 'slug' => 'rashkov'],
                    ['name' => 'Катериновка', 'type' => 'село', 'zip' => 'MD-6617', 'slug' => 'katerinovka'],
                ],
            ],
        ];
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seller cabinet designs
    |--------------------------------------------------------------------------
    |
    | Cabinet themes override Blade files from resources/views/backend/cabinet
    | by placing files in resources/views/themes/{theme}/backend/cabinet.
    |
    */
    'themes' => [
        'legacy' => 'Старый кабинет',
        'new' => 'Новый кабинет',
    ],

    'default' => env('CABINET_DESIGN_DEFAULT', 'legacy'),
    'setting_key' => 'cabinet_design',
    'cache_key' => 'settings.cabinet_design',
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site designs
    |--------------------------------------------------------------------------
    |
    | A theme may override any Blade file by placing a file with the same
    | relative path in resources/views/themes/{theme}. Missing files fall
    | back to the existing views in resources/views.
    |
    */
    'themes' => [
        'legacy' => 'Старый дизайн',
        'new' => 'Новый дизайн',
        'joom' => 'Joom-style витрина',
    ],

    'default' => env('DESIGN_DEFAULT', 'legacy'),
    'setting_key' => 'site_design',
    'cache_key' => 'settings.site_design',
];

<?php

return [
    'enabled' => env('REGIONALITY_ENABLED', false),
    'urls_enabled' => env('REGIONAL_URLS_ENABLED', false),
    'seo_enabled' => env('REGIONAL_SEO_ENABLED', false),
    'cookie_name' => 'regional_settlement',
    'cookie_minutes' => 60 * 24 * 180,
    'reserved_slugs' => [
        'admin', 'account', 'login', 'register', 'api', 'storage',
        'catalog', 'search', 'sale', 'shops', 'product', 'control', 'sitemap.xml',
        'gobuyer', 'seller-help', 'help',
    ],
];

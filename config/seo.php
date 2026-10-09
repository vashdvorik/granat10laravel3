<?php

return [
    /*
    | Страна в микроразметке адреса (schema.org PostalAddress.addressCountry), двухбуквенный код ISO 3166-1.
    | Если у вас иное требование, задайте SEO_COUNTRY_CODE в .env.
    */
    'country' => env('SEO_COUNTRY_CODE', 'MD'),

    /*
    | Как называть территорию, на которой работает площадка, когда город не выбран.
    */
    'area_name' => env('SEO_AREA_NAME', 'Приднестровье'),

    /*
    | Ссылки на официальные страницы бренда (Instagram, Telegram, Facebook ...) через запятую.
    | Попадают в микроразметку Organization (sameAs): так поисковик связывает сайт с его аккаунтами.
    */
    'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_SAME_AS', ''))))),

    /*
    | Максимальное число записей в одном файле sitemap (по правилам Google — не больше 50 000).
    */
    'sitemap_chunk' => (int) env('SEO_SITEMAP_CHUNK', 40000),

    /*
    | Сколько секунд хранить готовый список страниц sitemap.xml (0 — не кешировать).
    */
    'sitemap_ttl' => (int) env('SEO_SITEMAP_TTL', 900),

    /*
    | Время кеширования счётчиков товаров/магазинов для описаний страниц, секунд.
    */
    'stats_ttl' => (int) env('SEO_STATS_TTL', 600),
];

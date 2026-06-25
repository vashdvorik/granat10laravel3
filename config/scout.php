<?php

use App\Models\Product;

return [
    'driver' => env('SCOUT_DRIVER', 'null'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue' => env('SCOUT_QUEUE', false),
    'after_commit' => true,
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    'soft_delete' => false,
    'identify' => false,
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Product::class => [
                'searchableAttributes' => [
                    'name',
                    'category_name',
                    'features',
                    'description',
                    'seller_name',
                ],
                'filterableAttributes' => [
                    'category_id',
                    'settlement_id',
                    'seller_id',
                ],
                'sortableAttributes' => [
                    'is_vip',
                    'push_at',
                ],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                ],
            ],
        ],
    ],
];

<?php

return [
    'ai_enabled' => env('PRODUCT_MODERATION_AI_ENABLED', false),
    'mode' => env('PRODUCT_MODERATION_AI_MODE', 'shadow'),
    'queue' => env('PRODUCT_MODERATION_QUEUE', 'moderation'),
    'timeout' => (int) env('PRODUCT_MODERATION_TIMEOUT', 60),
    'max_attempts' => (int) env('PRODUCT_MODERATION_MAX_ATTEMPTS', 3),

    // Единственный сервис ИИ — OpenRouter (https://openrouter.ai). Один ключ и для текста, и для фото;
    // ключ, введённый в админке, хранится зашифрованным в базе и имеет приоритет над OPENROUTER_API_KEY.
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'text_model' => env('OPENROUTER_TEXT_MODEL', 'openai/gpt-4o-mini'),
        'image_model' => env('OPENROUTER_IMAGE_MODEL', 'openai/gpt-4o-mini'),
    ],

    'http' => [
        'max_output_tokens' => (int) env('AI_MODERATION_MAX_OUTPUT_TOKENS', 1200),
        'retry_times' => (int) env('AI_MODERATION_RETRY_TIMES', 0),
        'retry_sleep_ms' => (int) env('AI_MODERATION_RETRY_SLEEP_MS', 500),
        'requests_per_minute' => (int) env('AI_MODERATION_REQUESTS_PER_MINUTE', 5),
        'rate_limit_safety_seconds' => (int) env('AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS', 1),
    ],

    // Сколько фото и какого размера отправляется на проверку
    'vision' => [
        'max_images' => (int) env('AI_MODERATION_VISION_MAX_IMAGES', 5),
        'max_image_bytes' => (int) env('AI_MODERATION_VISION_MAX_IMAGE_BYTES', 2_000_000),
    ],

    'images' => [
        'enabled' => (bool) env('AI_IMAGE_MODERATION_ENABLED', false),
        'queue' => env('AI_IMAGE_MODERATION_QUEUE', env('PRODUCT_MODERATION_QUEUE', 'moderation')),
        'timeout' => (int) env('AI_IMAGE_MODERATION_TIMEOUT', env('PRODUCT_MODERATION_TIMEOUT', 60)),
        'max_attempts' => (int) env('AI_IMAGE_MODERATION_MAX_ATTEMPTS', env('PRODUCT_MODERATION_MAX_ATTEMPTS', 3)),
        'audit' => [
            'prompt_version' => 'product-image-moderation-v1',
            'policy_version' => 'marketplace-image-policy-v1',
        ],
        'local_scan' => [
            'enabled' => (bool) env('AI_IMAGE_LOCAL_SCAN_ENABLED', true),
            'timeout' => (int) env('AI_IMAGE_LOCAL_SCAN_TIMEOUT', 15),
            'tesseract_binary' => env('AI_IMAGE_TESSERACT_BINARY', 'tesseract'),
            'tesseract_language' => env('AI_IMAGE_TESSERACT_LANGUAGE', 'eng'),
            'zbar_binary' => env('AI_IMAGE_ZBAR_BINARY', 'zbarimg'),
        ],
    ],
    'audit' => [
        'prompt_version' => 'product-moderation-v2',
        'policy_version' => 'marketplace-policy-v1',
    ],
    'budget' => [
        'hourly_requests' => (int) env('AI_MODERATION_HOURLY_REQUEST_LIMIT', 60),
        'daily_requests' => (int) env('AI_MODERATION_DAILY_REQUEST_LIMIT', 500),
    ],
    'auto_approve' => [
        'max_risk_score' => (float) env('PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK', 0.10),
        'excluded_category_aliases' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES', ''))
        ))),
    ],
];

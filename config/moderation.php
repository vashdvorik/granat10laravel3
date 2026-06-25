<?php

return [
    'ai_enabled' => env('PRODUCT_MODERATION_AI_ENABLED', false),
    'provider' => env('PRODUCT_MODERATION_AI_PROVIDER', 'fake'),
    'mode' => env('PRODUCT_MODERATION_AI_MODE', 'shadow'),
    'queue' => env('PRODUCT_MODERATION_QUEUE', 'moderation'),
    'timeout' => (int) env('PRODUCT_MODERATION_TIMEOUT', 60),
    'max_attempts' => (int) env('PRODUCT_MODERATION_MAX_ATTEMPTS', 3),
    'fake_decision' => env('PRODUCT_MODERATION_FAKE_DECISION', 'review'),
    'google' => [
        'api_key' => env('AI_MODERATION_API_KEY', env('GEMINI_API_KEY')),
        'model' => env('AI_MODERATION_MODEL', env('GEMINI_MODEL', 'gemini-3.5-flash')),
        'base_url' => env('AI_MODERATION_BASE_URL', env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta')),
    ],
    'openai_compatible' => [
        'api_key' => env('AI_MODERATION_API_KEY'),
        'model' => env('AI_MODERATION_MODEL'),
        'base_url' => env('AI_MODERATION_BASE_URL', 'https://api.openai.com/v1'),
        'chat_completions_path' => env('AI_MODERATION_CHAT_COMPLETIONS_PATH', '/chat/completions'),
        'vision_enabled' => (bool) env('AI_MODERATION_COMPATIBLE_VISION_ENABLED', false),
    ],
    'http' => [
        'max_output_tokens' => (int) env('AI_MODERATION_MAX_OUTPUT_TOKENS', env('GEMINI_MAX_OUTPUT_TOKENS', 1200)),
        'retry_times' => (int) env('AI_MODERATION_RETRY_TIMES', env('GEMINI_RETRY_TIMES', 0)),
        'retry_sleep_ms' => (int) env('AI_MODERATION_RETRY_SLEEP_MS', env('GEMINI_RETRY_SLEEP_MS', 500)),
        'requests_per_minute' => (int) env('AI_MODERATION_REQUESTS_PER_MINUTE', env('GEMINI_REQUESTS_PER_MINUTE', 5)),
        'rate_limit_safety_seconds' => (int) env('AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS', env('GEMINI_RATE_LIMIT_SAFETY_SECONDS', 1)),
    ],
    'vision' => [
        'enabled' => (bool) env('AI_MODERATION_VISION_ENABLED', true),
        'max_images' => (int) env('AI_MODERATION_VISION_MAX_IMAGES', 5),
        'max_image_bytes' => (int) env('AI_MODERATION_VISION_MAX_IMAGE_BYTES', 2_000_000),
    ],
    'images' => [
        'enabled' => (bool) env('AI_IMAGE_MODERATION_ENABLED', false),
        'provider' => env('AI_IMAGE_MODERATION_PROVIDER', 'google'),
        'queue' => env('AI_IMAGE_MODERATION_QUEUE', env('PRODUCT_MODERATION_QUEUE', 'moderation')),
        'timeout' => (int) env('AI_IMAGE_MODERATION_TIMEOUT', env('PRODUCT_MODERATION_TIMEOUT', 60)),
        'max_attempts' => (int) env('AI_IMAGE_MODERATION_MAX_ATTEMPTS', env('PRODUCT_MODERATION_MAX_ATTEMPTS', 3)),
        'google' => [
            'api_key' => env('AI_IMAGE_MODERATION_API_KEY', env('GEMINI_API_KEY')),
            'model' => env('AI_IMAGE_MODERATION_MODEL', env('GEMINI_MODEL', 'gemini-3.5-flash')),
            'base_url' => env('AI_IMAGE_MODERATION_BASE_URL', env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta')),
        ],
        'audit' => [
            'prompt_version' => 'product-image-moderation-v1',
            'policy_version' => 'marketplace-image-policy-v1',
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

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Защита форм регистрации и восстановления пароля
    |--------------------------------------------------------------------------
    | Лимиты считаются по IP и учитывают любые попытки (в том числе неудачные).
    */
    'register' => [
        'per_minute' => (int) env('REGISTER_RATE_PER_MINUTE', 5),
        'per_day' => (int) env('REGISTER_RATE_PER_DAY', 30),
    ],

    'forgot_password' => [
        'per_minute' => (int) env('FORGOT_PASSWORD_RATE_PER_MINUTE', 3),
        'per_day' => (int) env('FORGOT_PASSWORD_RATE_PER_DAY', 20),
    ],

    // Человек не заполнит форму быстрее этого времени; форма старше max — устарела
    'min_fill_seconds' => (int) env('FORM_MIN_FILL_SECONDS', 3),
    'max_form_age_minutes' => (int) env('FORM_MAX_AGE_MINUTES', 180),

    // Cloudflare Turnstile (бесплатная капча). Без ключей не используется.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
        'timeout' => 5,
    ],

    // Одноразовые почтовые сервисы: регистрация на них запрещена
    'disposable_domains' => [
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'guerrillamail.org', 'sharklasers.com',
        '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org', 'temp-mail.io',
        'tempmailo.com', 'tempail.com', 'yopmail.com', 'yopmail.net', 'trashmail.com', 'trashmail.de',
        'getnada.com', 'nada.email', 'dispostable.com', 'maildrop.cc', 'mailnesia.com', 'mintemail.com',
        'throwawaymail.com', 'fakeinbox.com', 'emailondeck.com', 'moakt.com', 'mohmal.com', 'spamgourmet.com',
        'mailcatch.com', 'discard.email', 'burnermail.io', 'inboxkitten.com', 'minutemail.com', 'tmpmail.org',
        'tmail.ws', 'mytemp.email', 'emailfake.com', 'fexbox.org', 'anonbox.net', 'mail.tm', 'tmpeml.com',
    ],
];

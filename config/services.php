<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 10),
    ],

    // Приём платежей картой «Web-платёж» (ЗАО «Агропромбанк»), см. docs/WebPayment-Документация.md
    'webpayment' => [
        'enabled' => (bool) env('WEBPAYMENT_ENABLED', false),
        'merchant_login' => env('WEBPAYMENT_MERCHANT_LOGIN'),
        'merchant_pass' => env('WEBPAYMENT_MERCHANT_PASS'),
        'payment_url' => env('WEBPAYMENT_PAYMENT_URL', 'https://epay.apb.online/PaymentStart'),
        'wsdl_url' => env('WEBPAYMENT_WSDL_URL', 'https://ws.agroprombank.com/merchant/APB.SV.WebPayment.AgentService.asmx?WSDL'),
        // Код валюты счёта по справочнику ЦБ ПМР (рубль ПМР — 000)
        'currency' => env('WEBPAYMENT_CURRENCY', '000'),
        // true — тестовые счета (IsTest=1). В рабочем режиме тестовые оплаты не зачисляются.
        'is_test' => (bool) env('WEBPAYMENT_TEST', true),
        'lifetime_minutes' => (int) env('WEBPAYMENT_LIFETIME', 30),
        'min_amount' => (int) env('WEBPAYMENT_MIN_AMOUNT', 10),
        'max_amount' => (int) env('WEBPAYMENT_MAX_AMOUNT', 50000),
        'timeout' => (int) env('WEBPAYMENT_TIMEOUT', 15),
        // Необязательно: путь к сертификату банка (PEM) для проверки электронной подписи ответов
        'bank_certificate' => env('WEBPAYMENT_BANK_CERT'),
    ],

];

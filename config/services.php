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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key'        => env('GEMINI_API_KEY'),
        'model'      => env('GEMINI_MODEL', 'gemini-2.5-flash-lite'),
        'max_tokens' => env('GEMINI_MAX_OUTPUT_TOKENS', 1500),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'fonnte'),
    ],

    'fonnte' => [
        'token'   => env('FONNTE_TOKEN'),
        'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
    ],

    'waha' => [
        'base_url' => env('WAHA_BASE_URL', 'http://127.0.0.1:3000'),
        'session'  => env('WAHA_SESSION', 'default'),
        'api_key'  => env('WAHA_API_KEY'),
    ],

    'duitku' => [
        'merchant_code' => env('DUITKU_MERCHANT_CODE'),
        'api_key'       => env('DUITKU_API_KEY'),
        'callback_url'  => env('DUITKU_CALLBACK_URL'),
        'return_url'    => env('DUITKU_RETURN_URL'),
        'is_production' => env('DUITKU_IS_PRODUCTION', true),
    ],

];

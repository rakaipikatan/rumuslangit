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
    ],

    // SumoPod Managed Payment (QRIS). Isi nilai di .env — jangan di-commit.
    'sumopod_pay' => [
        'base_url'       => env('SUMOPOD_PAY_BASE_URL', 'https://api-pay.sumopod.com'),
        'api_key'        => env('SUMOPOD_PAY_API_KEY'),
        'webhook_secret' => env('SUMOPOD_PAY_WEBHOOK_SECRET'),
    ],

    // xAI Grok — penyedia AI CADANGAN bila Gemini gagal. Aktif hanya bila key DAN model terisi di .env.
    // Gunakan tombol "Tes Grok" di Admin → Sistem untuk melihat daftar model yang tersedia bagi akun Anda.
    'xai' => [
        'key'      => env('XAI_API_KEY'),
        'model'    => env('XAI_MODEL'),
        'base_url' => env('XAI_BASE_URL', 'https://api.x.ai/v1'),
        'timeout'  => env('XAI_TIMEOUT', 120),
    ],

];

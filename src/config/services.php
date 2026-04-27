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

    'waha' => [
        'base_url' => env('WAHA_BASE_URL', 'http://127.0.0.1:3000'),
        'api_key' => env('WAHA_API_KEY'),
        'webhook_token' => env('WAHA_WEBHOOK_TOKEN'),
        'webhook_hmac_key' => env('WAHA_WEBHOOK_HMAC_KEY'),
        'send_text_path' => env('WAHA_SEND_TEXT_PATH', '/api/sendText'),
        'notification_session' => env('WAHA_NOTIFICATION_SESSION', 'default'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1/responses'),
    ],

];

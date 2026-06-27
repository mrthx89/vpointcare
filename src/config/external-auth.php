<?php

return [
    'registration_enabled' => env('EXTERNAL_REGISTRATION_ENABLED', true),
    'default_status' => env('EXTERNAL_REGISTRATION_DEFAULT_STATUS', 'pending'),
    'rate_limit' => (int) env('EXTERNAL_AUTH_RATE_LIMIT', 10),

    'google' => [
        'enabled' => env('GOOGLE_AUTH_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'allowed_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_ALLOWED_DOMAINS', ''))))),
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'issuer' => 'https://accounts.google.com',
    ],

    'sso' => [
        'enabled' => env('SSO_AUTH_ENABLED', false),
        'name' => env('SSO_DISPLAY_NAME', 'SSO Perusahaan'),
        'provider' => env('SSO_PROVIDER', 'oidc'),
        'client_id' => env('SSO_CLIENT_ID'),
        'client_secret' => env('SSO_CLIENT_SECRET'),
        'redirect_uri' => env('SSO_REDIRECT_URI'),
        'issuer_url' => rtrim((string) env('SSO_ISSUER_URL', ''), '/'),
        'authorize_url' => env('SSO_AUTHORIZE_URL'),
        'token_url' => env('SSO_TOKEN_URL'),
        'userinfo_url' => env('SSO_USERINFO_URL'),
        'allowed_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('SSO_ALLOWED_DOMAINS', ''))))),
    ],
];

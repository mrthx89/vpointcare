<?php

return [
    'default' => env('APP_LOCALE', 'id'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    'cookie' => 'wacs_locale',
    'session_key' => 'wacs_locale',
    'supported' => [
        'id' => [
            'label' => 'Indonesia',
            'short' => 'ID',
            'native' => 'Indonesia',
            'regional' => 'id_ID',
            'flag' => 'ID',
        ],
        'en' => [
            'label' => 'English',
            'short' => 'EN',
            'native' => 'English',
            'regional' => 'en_US',
            'flag' => 'EN',
        ],
    ],
];

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
            'date' => 'd M Y',
            'date_short' => 'd M',
            'date_input' => 'd-m-Y',
            'datetime' => 'd M Y H:i:s',
            'datetime_short' => 'd M H:i:s',
            'time' => 'H:i',
        ],
        'en' => [
            'label' => 'English',
            'short' => 'EN',
            'native' => 'English',
            'regional' => 'en_US',
            'flag' => 'EN',
            'date' => 'M j, Y',
            'date_short' => 'M j',
            'date_input' => 'm-d-Y',
            'datetime' => 'M j, Y H:i:s',
            'datetime_short' => 'M j H:i:s',
            'time' => 'H:i',
        ],
    ],
];

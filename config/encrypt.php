<?php

return [
    'default' => [
        'driver' => 'openssl',
        /**
         * APP_KEY from .env; "base64:" keys are decoded. Generate one with: php kohana key:generate
         */
        'key' => env('APP_KEY'),
        'method' => env('APP_CIPHER', 'AES-256-CTR'),
    ],
];

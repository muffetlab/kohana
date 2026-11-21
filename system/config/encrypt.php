<?php

return [
    'default' => [
        'driver' => 'openssl',
        /**
         * The following options must be set:
         *
         * string   key     Secret passphrase.
         * integer  method  The cipher method, one of the return value from openssl_get_cipher_methods().
         */
        // APP_KEY from .env; "base64:" keys are decoded. Generate one with: php kohana key:generate
        'key' => env('APP_KEY'),
        'method' => 'AES-256-CTR',
    ],
];

<?php

return [
    /**
     * Salt that signs cookies. Generate one with: php kohana key:generate
     */
    'salt' => env('COOKIE_SALT'),

    /**
     * Lifetime in seconds; 0 lasts until the browser closes.
     */
    'expiration' => (int) env('COOKIE_EXPIRATION', 0),

    'path' => env('COOKIE_PATH', '/'),
    'domain' => env('COOKIE_DOMAIN', ''),

    /**
     * Only send cookies over HTTPS; turn on in production.
     */
    'secure' => env('COOKIE_SECURE', false),

    /**
     * Hide cookies from JavaScript.
     */
    'httponly' => env('COOKIE_HTTPONLY', true),
];

<?php

return [
    /**
     * Session driver used by Session::instance(): native, cookie, database or redis.
     */
    'driver' => env('SESSION_DRIVER', 'native'),

    'native' => [
        'name' => env('SESSION_NAME', 'session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 0),
        // Where PHP stores the session files
        'save_path' => storage_path('framework/sessions'),
    ],

    'cookie' => [
        'name' => env('SESSION_NAME', 'session'),
        'encrypted' => false,
        'lifetime' => (int) env('SESSION_LIFETIME', 0),
    ],

    /**
     * Redis sessions (cache module, ext-redis): uses a Redis store of config/cache.php.
     */
    'redis' => [
        'name' => env('SESSION_NAME', 'session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 0),
        // Seconds an idle session is kept
        'ttl' => (int) env('SESSION_TTL', 7200),
        'store' => env('SESSION_STORE', 'redis'),
        'prefix' => 'session:',
    ],

    /**
     * Database sessions (database module): php kohana session:migration creates the table.
     */
    'database' => [
        'name' => env('SESSION_NAME', 'session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 0),
        'group' => env('SESSION_CONNECTION', 'default'),
        'table' => 'sessions',
        'gc' => 500,
        'columns' => [
            'session_id' => 'session_id',
            'last_active' => 'last_active',
            'contents' => 'contents',
        ],
    ],
];

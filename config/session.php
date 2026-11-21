<?php

return [
    /**
     * Session driver used by Session::instance(): native, cookie or database.
     */
    'driver' => env('SESSION_DRIVER', 'native'),

    'native' => [
        'name' => env('SESSION_NAME', 'session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 0),
    ],

    'cookie' => [
        'name' => env('SESSION_NAME', 'session'),
        'encrypted' => false,
        'lifetime' => (int) env('SESSION_LIFETIME', 0),
    ],

    /**
     * Database sessions (database module): create the table with a migration.
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

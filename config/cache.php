<?php

return [
    /**
     * Store used by Cache::instance() without a name.
     */
    'default' => env('CACHE_STORE', 'file'),

    'file' => [
        'driver' => 'file',
        'cache_dir' => storage_path('framework/cache'),
        'default_expire' => 3600,
        'ignore_on_delete' => [
            '.gitignore',
            '.git',
            '.svn',
        ],
    ],

    'apcu' => [
        'driver' => 'apcu',
        'default_expire' => 3600,
    ],

    'redis' => [
        'driver' => 'redis',
        'default_expire' => 3600,
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD'),
        'database' => (int) env('REDIS_CACHE_DB', 0),
        'prefix' => env('CACHE_PREFIX', 'kohana:'),
    ],

    'memcached' => [
        'driver' => 'memcached',
        'default_expire' => 3600,
        'servers' => [
            'local' => [
                'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => (int) env('MEMCACHED_PORT', 11211),
                'weight' => 1,
            ],
        ],
    ],

    'sqlite' => [
        'driver' => 'sqlite',
        'default_expire' => 3600,
        'database' => storage_path('framework/cache/kohana-cache.sql3'),
        'schema' => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
    ],
];

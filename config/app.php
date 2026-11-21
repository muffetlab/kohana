<?php

return [
    /**
     * Environment: production, staging, testing or development ("local" means
     * development). KOHANA_ENV is still read when APP_ENV is not set.
     */
    'env' => env('APP_ENV', env('KOHANA_ENV', 'development')),

    /**
     * Path, and optionally the domain, the application is served from.
     */
    'base_url' => env('APP_BASE_URL', '/'),

    /**
     * Front controller in generated URLs. Set APP_INDEX_FILE= (empty) when the
     * web server rewrites URLs to index.php (see public/example.htaccess).
     */
    'index_file' => env('APP_INDEX_FILE', 'index.php'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /**
     * Language for translations (lang/) and the C locale for setlocale().
     */
    'locale' => env('APP_LOCALE', 'en-us'),
    'system_locale' => env('APP_SYSTEM_LOCALE', 'en_US.utf-8'),

    'charset' => 'utf-8',

    /**
     * Kohana's error and exception handlers. Production hides error details.
     */
    'errors' => env('APP_ERRORS', true),

    /**
     * Cache the cascading filesystem's file lookups; turn on in production.
     */
    'caching' => env('APP_CACHING', false),
    'cache_dir' => storage_path('framework/cache'),
    'cache_life' => 60,

    /**
     * Internal profiling (Profiler).
     */
    'profile' => env('APP_PROFILE', false),

    /**
     * Send an X-Powered-By header.
     */
    'expose' => false,

    /**
     * Modules to enable, before the ones installed with Composer. A module is
     * a name => directory pair.
     */
    'modules' => [
        // 'auth' => MODPATH . 'auth',         // Basic authentication
        // 'cache' => MODPATH . 'cache',       // Caching with multiple backends
        // 'database' => MODPATH . 'database', // Database access, migrations and seeders
        // 'image' => MODPATH . 'image',       // Image manipulation
        // 'minion' => MODPATH . 'minion',     // Console commands (always loaded by php kohana)
        // 'orm' => MODPATH . 'orm',           // Object Relationship Mapping
    ],
];

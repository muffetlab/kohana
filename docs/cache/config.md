# Kohana Cache configuration

Kohana Cache uses configuration groups to create cache instances. A configuration group can use any supported driver, with successive groups using multiple instances of the same driver type.

The default cache group is loaded based on the `Cache::$default` setting. It is set to the `file` driver as standard, however this can be changed within the `/app/boostrap.php` file.

    // Change the default cache driver to memcached
    Cache::$default = 'memcached';

    // Load the memcached cache driver using default setting
    $memcached = Cache::instance();

## Group settings

Below are the default cache configuration groups for each supported driver. Add to or override these settings within the `config/cache.php` file.

| Name           | Required | Description                              |
|----------------|----------|------------------------------------------|
| driver         | __YES__  | (_string_) The driver type to use        |
| default_expire | __NO__   | (_integer_) The default expiration value |

    'file' => [
        'driver' => 'file',
        'cache_dir' => storage_path('framework/cache'),
        'default_expire' => 3600,
    ],

## Memcached settings

| Name    | Required | Description                                                                                                              |
|---------|----------|--------------------------------------------------------------------------------------------------------------------------|
| driver  | __YES__  | (_string_) The driver type to use                                                                                        |
| servers | __YES__  | (_array_) Associative array of server details, must include a __host__ key. (See _Memcached server configuration_ below) |

### Memcached server configuration

| Name    | Required | Description                                                                                                                                                                                   |
|---------|----------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| host    | __YES__  | (_string_) The host of the memcache server, i.e. __localhost__; or __127.0.0.1__; or __memcache.domain.tld__                                                                                  |
| port    | __NO__   | (_integer_) The port on which memcached is running. Set this parameter to 0 when using UNIX domain sockets. Default to __11211__                                                              |
| weight  | __NO__   | (_integer_) The weight of the server relative to the total weight of all the servers in the pool. This controls the probability of the server being selected for operations. Default to __1__ |
| options | __NO__   | (_array_) An associative array of options where the key is the option to set and the value is the new value for the option. Default to __[]__                                                 |

    'memcached' => [
        'driver' => 'memcached',
        'default_expire' => 3600,
        'servers' => [
            'local' => [
                'host' => 'localhost',
                'port' => 11211,
                'weight' => 1,
                'options' => []
            ],
        ]
    ],

## Redis settings

Requires the [phpredis](https://github.com/phpredis/phpredis) extension (`ext-redis`). Works with Redis and Valkey.

| Name       | Required | Description                                                                                       |
|------------|----------|---------------------------------------------------------------------------------------------------|
| driver     | __YES__  | (_string_) `redis`                                                                                |
| host       | __NO__   | (_string_) Host name or unix socket path. Default `127.0.0.1`                                    |
| port       | __NO__   | (_integer_) Port. Default `6379`, ignored for sockets                                             |
| password   | __NO__   | (_string_ or _array_) Password, or `[username, password]` for an ACL user                         |
| database   | __NO__   | (_integer_) Database index. Default `0`                                                           |
| timeout    | __NO__   | (_float_) Connection timeout in seconds. Default `1.0`                                            |
| persistent | __NO__   | (_boolean_) Reuse the connection between requests. Default `false`                                |
| prefix     | __NO__   | (_string_) Prefix for every key. Default `kohana:`. `deleteAll()` removes only keys with this prefix; an empty prefix flushes the whole database |

    'redis' => [
        'driver' => 'redis',
        'default_expire' => 3600,
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'prefix' => 'kohana:',
    ],

A lifetime of `0` stores an item without expiry. The store supports `increment()`/`decrement()` (they return `false` for a missing key, like Memcached) and tags (`setWithTags()`, `find()`, `deleteTag()`). `Cache::instance('redis')->redis()` returns the underlying `\Redis` connection for other commands.

## APCu settings

    'apcu' => [
        'driver' => 'apcu',
        'default_expire' => 3600,
    ],

## SQLite settings

    'sqlite' => [
        'driver' => 'sqlite',
        'default_expire' => 3600,
        'database' => storage_path('framework/cache/kohana-cache.sql3'),
        'schema' => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY,
            tags VARCHAR(255), expiration INTEGER, cache TEXT)',
    ],

## File settings

    'file' => [
        'driver' => 'file',
        'cache_dir' => storage_path('framework/cache'),
        'default_expire' => 3600,
    ],

## Override existing configuration group

The following example demonstrates how to override an existing configuration setting, using the config file in `/config/cache.php`.

    <?php

    return [
        // Override the default configuration
        'memcached' => [
            // Use Memcached as the default driver
            'driver' => 'memcached',
            // Overide default expiry
            'default_expire' => 8000,
            'servers' => [
                // Add a new server
                [
                    'host' => 'cache.domain.tld',
                    'port' => 11211,
                ],
            ]
        ],
    ];

## Add new configuration group

The following example demonstrates how to add a new configuration setting, using the config file in `/config/cache.php`.

    <?php

    return [
        // Override the default configuration
        'fastkv' => [
            // Use APCu as the default driver
            'driver' => 'apcu',
            // Overide default expiry
            'default_expire' => 1000,
        ],
    ];

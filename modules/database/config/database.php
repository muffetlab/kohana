<?php

return [
    /**
     * The default connection reads its settings from the environment (.env).
     *
     * DB_CONNECTION  mysql (MySQLi), pgsql (PostgreSQL), PDO, or a driver class name
     * DB_HOST        Server hostname, or socket path
     * DB_PORT        Port number (3306 for MySQL, 5432 for PostgreSQL)
     * DB_DATABASE    Database name
     * DB_USERNAME    Database username
     * DB_PASSWORD    Database password
     * DB_DSN         PDO data source name, only for DB_CONNECTION=PDO
     * DB_PREFIX      Table prefix
     * DB_CHARSET     Connection character set (utf8mb4 for MySQL, UTF8 for PostgreSQL)
     */
    'default' => [
        'type' => env('DB_CONNECTION', 'mysql'),
        'connection' => [
            'hostname' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT'),
            'database' => env('DB_DATABASE', 'kohana'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'dsn' => env('DB_DSN'),
            'persistent' => false,
        ],
        'table_prefix' => env('DB_PREFIX', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ],
    /**
     * More connections are configured the same way, for example a read replica:
     *
     * 'replica' => [
     *     'type' => 'mysql',
     *     'connection' => [
     *         'hostname' => env('DB_REPLICA_HOST'),
     *         'database' => env('DB_DATABASE'),
     *         'username' => env('DB_USERNAME'),
     *         'password' => env('DB_PASSWORD'),
     *     ],
     *     'charset' => 'utf8mb4',
     * ],
     *
     * MySQLi also accepts: socket, flags, ssl (client_key_path, client_cert_path,
     * ca_cert_path, ca_dir_path, cipher) and variables (session variables).
     */
];

<?php

/**
 * Database groups the tests use, from TEST_* environment variables (CI sets
 * them; the defaults match a local MySQL and PostgreSQL without passwords).
 * Tests skip themselves when a server is not reachable.
 */
$mysql = [
    'hostname' => env('TEST_MYSQL_HOST', '127.0.0.1'),
    'port' => (int) env('TEST_MYSQL_PORT', 3306),
    'database' => env('TEST_MYSQL_DATABASE', 'kohana'),
    'username' => env('TEST_MYSQL_USERNAME', 'root'),
    'password' => env('TEST_MYSQL_PASSWORD', ''),
    'persistent' => false,
];

return [
    // The generic PDO driver
    'default' => [
        'type' => 'PDO',
        'connection' => [
            'dsn' => "mysql:host={$mysql['hostname']};port={$mysql['port']};dbname={$mysql['database']}",
            'username' => $mysql['username'],
            'password' => $mysql['password'],
            'persistent' => false,
        ],
        'table_prefix' => '',
        'charset' => 'utf8mb4',
    ],
    'mysqli' => [
        'type' => 'MySQLi',
        'connection' => $mysql,
        'table_prefix' => '',
        'charset' => 'utf8mb4',
    ],
    'pgsql' => [
        'type' => 'pgsql',
        'connection' => [
            'hostname' => env('TEST_PGSQL_HOST', '127.0.0.1'),
            'port' => (int) env('TEST_PGSQL_PORT', 5432),
            'database' => env('TEST_PGSQL_DATABASE', 'kohana'),
            'username' => env('TEST_PGSQL_USERNAME', 'postgres'),
            'password' => env('TEST_PGSQL_PASSWORD', ''),
            'persistent' => false,
        ],
        'table_prefix' => '',
        'charset' => 'UTF8',
    ],
];

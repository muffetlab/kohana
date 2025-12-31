<?php

return [
    'default' => [
        'type' => 'PDO',
        'connection' => [
            /**
             * The following options are available for PDO:
             *
             * string   dsn         Data Source Name
             * string   username    Database username
             * string   password    Database password
             * bool     persistent  Use persistent connections?
             */
            'dsn' => 'mysql:host=localhost;dbname=kohana',
            'username' => '',
            'password' => '',
            'persistent' => false,
        ],
        'table_prefix' => '',
        'charset' => 'utf8',
    ],
    'mysqli' => [
        'type' => 'MySQLi',
        'connection' => [
            /**
             * The following options are available for MySQLi:
             *
             * string   hostname    Server hostname, or socket
             * string   username    Database username
             * string   password    Database password
             * string   database    Database name
             * string   port        Port number
             * string   socket      Unix domain socket
             * array    flags       Connection options
             * array    ssl         SSL parameters as "key => value" pairs
             *                      Available keys: client_key_path, client_cert_path, ca_cert_path, ca_dir_path, cipher
             * array    variables   Session variables as "key => value" pairs
             */
            'hostname' => 'localhost',
            'username' => '',
            'password' => '',
            'database' => 'kohana',
            'port' => 3306,
            'socket' => null,
            'flags' => null,
            'ssl' => null,
            'variables' => null,
        ],
        'table_prefix' => '',
        'charset' => 'utf8',
    ],
];

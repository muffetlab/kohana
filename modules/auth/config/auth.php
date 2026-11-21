<?php

use Kohana\Session\Session;

return [
    'driver' => 'File',
    // Algorithm for password_hash(); null uses Argon2id, or bcrypt when PHP lacks Argon2 support
    'hash_algorithm' => null,
    // Options for password_hash(), e.g. ['memory_cost' => 65536] or ['cost' => 12] for bcrypt
    'hash_options' => [],
    // Kohana 3 HMAC settings: only used to verify (and then rehash) passwords stored before Kohana 4
    'hash_method' => 'sha256',
    'hash_key' => null,
    'lifetime' => 1209600,
    'session_type' => Session::$default,
    'session_key' => 'auth_user',
    // Username => password hash combinations for the Auth File driver.
    // Create a hash with: php -r "echo password_hash('secret', PASSWORD_ARGON2ID), PHP_EOL;"
    'users' => [
    // 'admin' => '$argon2id$v=19$m=65536,t=4,p=1$...',
    ],
];

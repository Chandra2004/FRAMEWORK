<?php

/**
 * Database Configuration — The Framework v5.0.1
 * All values driven by .env
 */

return [
    'default' => ($_ENV['APP_ENV'] ?? 'production') === 'testing' ? 'sqlite' : ($_ENV['DB_CONNECTION'] ?? 'mysql'),

    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => ($_ENV['APP_ENV'] ?? 'production') === 'testing' 
                ? ($_ENV['DB_NAME_TESTING'] ?? 'the_framework_db') 
                : ($_ENV['DB_NAME'] ?? 'the_framework_db'),
            'username'  => $_ENV['DB_USER'] ?? 'root',
            'password'  => $_ENV['DB_PASS'] ?? '',
            'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
            'prefix'    => $_ENV['DB_PREFIX'] ?? '',
            'timezone'  => $_ENV['DB_TIMEZONE'] ?? '+07:00',
            'strict'    => filter_var($_ENV['DB_STRICT'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ],
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ],
    ],

    'redis' => [
        'host'     => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'password' => ($_ENV['REDIS_PASSWORD'] ?? null) === 'null' ? null : ($_ENV['REDIS_PASSWORD'] ?? null),
        'port'     => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'database' => (int) ($_ENV['REDIS_DB'] ?? 0),
    ],
];

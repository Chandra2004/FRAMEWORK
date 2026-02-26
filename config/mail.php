<?php

/**
 * Mail Configuration — The Framework v5.0.3
 * All values driven by .env
 */

return [
    'default' => $_ENV['MAIL_MAILER'] ?? 'smtp',

    'mailers' => [
        'smtp' => [
            'transport'  => 'smtp',
            'host'       => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
            'port'       => (int) ($_ENV['MAIL_PORT'] ?? 2525),
            'username'   => $_ENV['MAIL_USERNAME'] ?? null,
            'password'   => $_ENV['MAIL_PASSWORD'] ?? null,
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'timeout'    => (int) ($_ENV['MAIL_TIMEOUT'] ?? 30),
        ],
    ],

    'from' => [
        'address' => $_ENV['MAIL_FROM'] ?? 'noreply@example.com',
        'name'    => $_ENV['MAIL_FROM_NAME'] ?? 'The Framework',
    ],
];

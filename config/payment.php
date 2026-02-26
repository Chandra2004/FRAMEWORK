<?php

/**
 * Payment Gateway Configuration — The Framework v5.0.3
 * All values driven by .env
 */

return [
    'default' => $_ENV['PAYMENT_GATEWAY'] ?? 'midtrans',

    'midtrans' => [
        'server_key'    => $_ENV['MIDTRANS_SERVER_KEY'] ?? null,
        'client_key'    => $_ENV['MIDTRANS_CLIENT_KEY'] ?? null,
        'is_production' => filter_var($_ENV['MIDTRANS_IS_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'is_sanitized'  => true,
        'is_3ds'        => true,
    ],
];

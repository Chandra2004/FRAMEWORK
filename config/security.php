<?php

/**
 * Security & System Configuration — The Framework v5.0.1
 * Configuration for Web Command Center, WAF, and CORS.
 */

return [
    // =========================================================
    //  WEB COMMAND CENTER
    // =========================================================
    'allow_web_migration' => filter_var($_ENV['ALLOW_WEB_MIGRATION'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'system_allowed_ips'  => array_map('trim', explode(',', $_ENV['SYSTEM_ALLOWED_IPS'] ?? '127.0.0.1')),
    'system_auth_user'    => $_ENV['SYSTEM_AUTH_USER'] ?? '',
    'system_auth_pass'    => $_ENV['SYSTEM_AUTH_PASS'] ?? '',

    // =========================================================
    //  CORS (Cross-Origin Resource Sharing)
    // =========================================================
    'cors_allowed_origins'  => array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*')),
    'cors_allowed_methods'  => $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
    'cors_allowed_headers'  => $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With,X-CSRF-TOKEN',
    'cors_max_age'          => (int) ($_ENV['CORS_MAX_AGE'] ?? 86400),

    // =========================================================
    //  SECURITY KEYS
    // =========================================================
    'encryption_key'  => $_ENV['ENCRYPTION_KEY'] ?? '',
    'app_key'         => $_ENV['APP_KEY'] ?? '',
    'api_secret_key'  => $_ENV['API_SECRET_KEY'] ?? '',
    'jwt_secret'      => $_ENV['JWT_SECRET'] ?? '',
    'jwt_ttl'         => (int) ($_ENV['JWT_TTL'] ?? 60), // minutes
];

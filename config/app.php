<?php

/**
 * Application Configuration — The Framework v5.0.3
 * 
 * Semua konfigurasi utama aplikasi dikontrol dari sini,
 * yang nilainya diambil dari file .env.
 */

return [

    // =========================================================
    //  APPLICATION CORE
    // =========================================================
    'name'     => $_ENV['APP_NAME'] ?? 'The Framework',
    'env'      => $_ENV['APP_ENV'] ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['BASE_URL'] ?? 'http://localhost:8080',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta',
    'locale'   => $_ENV['APP_LOCALE'] ?? 'id',
    'fallback_locale' => $_ENV['APP_FALLBACK_LOCALE'] ?? 'en',
    'supported_locales' => array_map('trim', explode(',', $_ENV['APP_SUPPORTED_LOCALES'] ?? 'en,id')),
    'key'      => $_ENV['APP_KEY'] ?? '',

    // =========================================================
    //  LOGGING
    // =========================================================
    'log_channel'  => $_ENV['LOG_CHANNEL'] ?? 'daily',
    'log_level'    => $_ENV['LOG_LEVEL'] ?? 'debug',
    'log_max_days' => (int) ($_ENV['LOG_MAX_DAYS'] ?? 14),

    // =========================================================
    //  SESSION
    // =========================================================
    'session_driver'   => $_ENV['SESSION_DRIVER'] ?? 'file',
    'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
    'session_secure'   => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'session_http_only' => filter_var($_ENV['SESSION_HTTP_ONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'session_same_site' => $_ENV['SESSION_SAME_SITE'] ?? 'Lax',

    // =========================================================
    //  CACHE
    // =========================================================
    'cache_driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
    'cache_prefix' => $_ENV['CACHE_PREFIX'] ?? 'tf_cache_',
    'cache_ttl'    => (int) ($_ENV['CACHE_TTL'] ?? 3600),

    // =========================================================
    //  RATE LIMITER
    // =========================================================
    'rate_limit_enabled' => filter_var($_ENV['RATE_LIMIT_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'rate_limit_max'     => (int) ($_ENV['RATE_LIMIT_MAX'] ?? 100),
    'rate_limit_decay'   => (int) ($_ENV['RATE_LIMIT_DECAY'] ?? 120),
    'api_rate_limit_max' => (int) ($_ENV['API_RATE_LIMIT_MAX'] ?? 60),
    'api_rate_limit_decay' => (int) ($_ENV['API_RATE_LIMIT_DECAY'] ?? 60),

    // =========================================================
    //  UPLOAD
    // =========================================================
    'upload_max_size'  => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10240), // in KB
    'upload_webp'      => filter_var($_ENV['UPLOAD_AUTO_WEBP'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'upload_webp_quality' => (int) ($_ENV['UPLOAD_WEBP_QUALITY'] ?? 80),

    // =========================================================
    //  MAINTENANCE MODE
    // =========================================================
    'maintenance'      => filter_var($_ENV['APP_MAINTENANCE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'maintenance_ips'  => array_map('trim', explode(',', $_ENV['MAINTENANCE_IPS'] ?? '')),
    'maintenance_secret' => $_ENV['MAINTENANCE_SECRET'] ?? null,
];

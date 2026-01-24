<?php

use TheFramework\BladeInit;
use TheFramework\App\Config;
use TheFramework\App\Router;
use TheFramework\App\SessionManager;
use TheFramework\Http\Controllers\Services\FileController;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\App\Container;
use TheFramework\App\Database;
use TheFramework\App\Request;
use TheFramework\App\Exceptions\Handler;
use TheFramework\Helpers\Helper;
use TheFramework\Services\UserService;

SessionManager::startSecureSession();
Config::loadEnv();

// --- 🌟 ALL-IN ERROR HANDLING SYSTEM 🌟 ---
// HANYA AKTIF JIKA BUKAN TESTING
if (Config::get('APP_ENV') !== 'testing') {
    Handler::register();
}

// Setting Display Errors untuk PHP native (Backup)
$debug = Config::get('APP_DEBUG', 'false') === 'true';
ini_set('display_errors', $debug ? 1 : 0);
ini_set('display_startup_errors', $debug ? 1 : 0);
error_reporting(E_ALL);

// Hanya jalankan logic HTTP spesifik jika bukan CLI
if (php_sapi_name() !== 'cli') {
    // header('X-Powered-By: TheFramework-v1');
    // header('X-Frame-Options: DENY');
    // header('X-Content-Type-Options: nosniff');
    // header('X-XSS-Protection: 1; mode=block');
    // header('Referrer-Policy: no-referrer-when-downgrade');
    // header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

    // Rate Limiting Global
    // Menggunakan Helper::get_client_ip() untuk akurasi lebih baik (Proxy support)
    $ip = class_exists(Helper::class) ? Helper::get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    TheFramework\App\RateLimiter::check($ip, 100, 120);

    CsrfMiddleware::generateToken();
}

// --- 🚀 OPTIMIZED CONTAINER BINDING 🚀 ---
// Daftarkan class Core secara explisit agar Container TIDAK menggunakan Reflection (Lambat).
$container = Container::getInstance();

// 1. Database (Singleton)
$container->singleton(Database::class, function () {
    return Database::getInstance();
});

// 2. Request (Singleton per request cycle)
$container->singleton(Request::class, function () {
    return new Request();
});

// 3. Helper Service (Singleton if stateless)
$container->singleton(UserService::class, function () {
    return new UserService();
});

// --- ROUTING SYSTEM ---
// Route File Serving Route (Private Uploads)
Router::add('GET', '/file/(.*)', FileController::class, 'Serve');

BladeInit::init();

return $container;

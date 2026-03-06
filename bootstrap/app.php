<?php

use TheFramework\App\Cache\CacheManager;
use TheFramework\BladeInit;
use TheFramework\App\Core\Config;
use TheFramework\App\Http\Router;
use TheFramework\App\Http\SessionManager;
use TheFramework\App\Internal\Controllers\FileController;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\App\Core\Container;
use TheFramework\App\Database\Database;
use TheFramework\App\Http\Request;
use TheFramework\App\Exceptions\Handler;
use TheFramework\App\Http\RateLimiter;
use TheFramework\Helpers\Helper;
use TheFramework\Services\UserService;
use TheFramework\Repositories\UserRepository;

SessionManager::startSecureSession();
Config::loadEnv();
CacheManager::configure();

// --- 📁 AUTO-INITIALIZE STORAGE STRUCTURE 📁 ---
if (Config::get('APP_ENV') !== 'testing') {
    $storageRoot = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
    $requiredDirs = [
        $storageRoot . '/storage/logs',
        $storageRoot . '/storage/session',
        $storageRoot . '/storage/framework',
        $storageRoot . '/storage/framework/views',
        $storageRoot . '/storage/framework/cache',
        $storageRoot . '/storage/app',
        $storageRoot . '/storage/app/public'
    ];

    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            // ✅ SECURITY FIX: Auto-create .gitignore to prevent sensitive files from being committed
            file_put_contents($dir . '/.gitignore', "*\n!.gitignore");
        }
    }
}

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
    // ✅ SECURITY FIX: Enabled security headers (were commented out!)
    if (Config::get('APP_DEBUG') === 'true') {
        header('X-Powered-By: TheFramework-v5.0.1');
    } else {
        header_remove('X-Powered-By');
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // 🔒 SECURITY FIX: Enable HSTS only on HTTPS (prevents warnings on HTTP dev)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // Rate Limiting Global
    // ✅ SECURITY FIX: Gunakan REMOTE_ADDR langsung agar tidak bisa di-spoof via header HTTP_CLIENT_IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    RateLimiter::check($ip, 100, 120);

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
$container->singleton(UserService::class, function ($c) {
    return new UserService($c->make(UserRepository::class));
});

// --- 🛠️ SERVICE PROVIDERS 🛠️ ---
$appProvider = new \TheFramework\Providers\AppServiceProvider();
$appProvider->register();
$appProvider->boot();

// --- ROUTING SYSTEM ---
// Route File Serving Route (Private Uploads)
foreach (FileController::getAllowedFolders() as $folder) {
    Router::add('GET', "/file/{$folder}/(.*)", FileController::class, 'Serve');
}

BladeInit::init();

return $container;

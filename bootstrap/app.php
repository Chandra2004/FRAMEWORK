<?php

use TheFramework\BladeInit;
use TheFramework\App\Config;
use TheFramework\App\Router;
use TheFramework\App\SessionManager;
use TheFramework\Http\Controllers\Services\FileController;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\Middleware\WAFMiddleware;
use TheFramework\App\Container;
use TheFramework\App\Database;
use TheFramework\App\Request;

SessionManager::startSecureSession();
Config::loadEnv();

header('X-Powered-By: Native-Chandra');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Rate Limiting Global
TheFramework\App\RateLimiter::check($_SERVER['REMOTE_ADDR'], 100, 120);

CsrfMiddleware::generateToken();

$container = Container::getInstance();

$container->singleton(Database::class, function () {
    return Database::getInstance();
});

$container->singleton(Request::class, function () {
    return new Request();
});

Router::add('GET', '/file/(.*)', FileController::class, 'Serve');

$cacheFile = __DIR__ . '/../bootstrap/cache/routes.php';
if (file_exists($cacheFile)) {
    $routes = include $cacheFile;
    foreach ($routes as $route) {
        Router::add(
            $route['method'],
            $route['path_original'] ?? $route['path'],
            $route['handler'],
            $route['function'],
            $route['middleware']
        );
    }
} else {
    require_once __DIR__ . '/../routes/web.php';
}

BladeInit::init();
Router::run();

<?php
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/app/Helpers/helpers.php';
    define('ROOT_DIR', dirname(__DIR__));

    use {{NAMESPACE}}\BladeInit;
    use {{NAMESPACE}}\App\Config;
    use {{NAMESPACE}}\App\Router;
    use {{NAMESPACE}}\App\SessionManager;
    use {{NAMESPACE}}\App\RateLimiter;
    use {{NAMESPACE}}\Middleware\CsrfMiddleware;
    use {{NAMESPACE}}\Middleware\WAFMiddleware;
    use {{NAMESPACE}}\Middleware\ValidationMiddleware;
    use {{NAMESPACE}}\Http\Controllers\HomeController;

    // Mulai sesi dengan aman
    SessionManager::startSecureSession();

    // Generate nonce untuk CSP
    $nonce = base64_encode(random_bytes(16));

    // Tambahkan header keamanan
    header('X-Powered-By: Native-Chandra');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    // header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self'; connect-src 'self';");

    // Terapkan rate limiting berdasarkan IP
    $clientIp = $_SERVER['REMOTE_ADDR'];
    RateLimiter::check("rate_limit:{$clientIp}", 1000, 120);

    // Load konfigurasi .env
    Config::loadEnv();

    // Inisialisasi token CSRF
    CsrfMiddleware::generateToken();

    // Definisi route aplikasi
    Router::add('GET', '/', HomeController::class, 'index', [WAFMiddleware::class]);
    Router::add('GET', '/user', HomeController::class, 'user', [WAFMiddleware::class]);
    Router::add('POST', '/user', HomeController::class, 'createUser', [WAFMiddleware::class, CsrfMiddleware::class, ValidationMiddleware::class]);
    Router::add('GET', '/user/information/{id}', HomeController::class, 'detail', [WAFMiddleware::class]);
    Router::add('GET', '/user/{id}/delete', HomeController::class, 'deleteUser', [WAFMiddleware::class, ValidationMiddleware::class]);
    Router::add('POST', '/user/{id}/update', HomeController::class, 'updateUser', [WAFMiddleware::class, CsrfMiddleware::class, ValidationMiddleware::class]);

    // Inisialisasi Blade untuk templating
    BladeInit::init();

    // Cache route agar lebih cepat (hanya jika mode production)
    Router::cacheRoutes();

    // Jalankan routing aplikasi
    Router::run();
?>

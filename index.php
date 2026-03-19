<?php
ob_start();

// Check PHP Version Early
if (version_compare(PHP_VERSION, '8.3.0') < 0) {
    header('HTTP/1.1 500 Internal Server Error');
    die("❌ Error: THE FRAMEWORK requires PHP 8.3 or higher. Your current version is " . PHP_VERSION);
}

// ✅ SARAN-B4-001: Pastikan buffer selalu di-flush saat skrip berakhir
register_shutdown_function(function () {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
});
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Helpers/helpers.php';

// Early Error Reporting (Replaced later by .env setting)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_DIR', __DIR__);

// 1. Load Environment & Core Services
require_once __DIR__ . '/bootstrap/app.php';

// --- 🚧 MAINTENANCE MODE CHECK ("Paten" Feature) 🚧 ---
// Check if app is down via .env or maintenance file
$isDown = \TheFramework\App\Core\Config::get('APP_MAINTENANCE', 'false') === 'true';
$maintenanceFile = __DIR__ . '/storage/framework/down';

if ($isDown || file_exists($maintenanceFile)) {
    // Check if current IP is allowed to bypass (for developers)
    $allowedIps = array_map('trim', explode(',', \TheFramework\App\Core\Config::get('MAINTENANCE_IPS', '')));
    // ✅ SECURITY FIX: Gunakan REMOTE_ADDR langsung agar tidak bisa di-spoof via header HTTP_CLIENT_IP
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!in_array($clientIp, $allowedIps)) {
        header('HTTP/1.1 503 Service Unavailable');
        header('Retry-After: 3600');
        if (file_exists(__DIR__ . '/app/App/Internal/Views/errors/maintenance.blade.php')) {
            echo \TheFramework\App\Http\View::render('Internal::errors.maintenance');
        } elseif (file_exists(__DIR__ . '/resources/views/errors/503.blade.php')) {
            echo \TheFramework\App\Http\View::render('errors.503');
        } else {
            die("🚧 Under Maintenance. Please check back later.");
        }
        exit;
    }
}

// 2. Load Routes (Environment is now available)
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/system.php';

// 3. Run Application
\TheFramework\App\Http\Router::run();

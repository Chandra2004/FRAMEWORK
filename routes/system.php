<?php

use TheFramework\App\Router;
use TheFramework\App\Migrator;
use TheFramework\App\Container;

/**
 * Multi-Layer Security Check for System Routes
 * v5.1.0 Security Enhancement
 */
function checkSystemKey()
{
    // === LAYER 1: Feature Toggle ===
    if (\TheFramework\App\Config::get('ALLOW_WEB_MIGRATION') !== 'true') {
        header('HTTP/1.0 403 Forbidden');
        die("⛔ FEATURE DISABLED: Web migration tools are disabled in configuration.");
    }

    // === LAYER 2: IP Whitelist ===
    $clientIp = \TheFramework\Helpers\Helper::get_client_ip();
    $allowedIps = \TheFramework\App\Config::get('SYSTEM_ALLOWED_IPS', '127.0.0.1');
    $ipWhitelist = array_map('trim', explode(',', $allowedIps));

    if (!in_array($clientIp, $ipWhitelist) && !in_array('*', $ipWhitelist)) {
        \TheFramework\App\Logging::getLogger()->warning(
            "System route access denied for IP: $clientIp",
            ['uri' => $_SERVER['REQUEST_URI'] ?? '']
        );
        header('HTTP/1.0 403 Forbidden');
        die("⛔ ACCESS DENIED: Your IP ($clientIp) is not whitelisted for system access.");
    }

    // === LAYER 3: Basic Auth (Required if configured) ===
    $sysUser = \TheFramework\App\Config::get('SYSTEM_AUTH_USER');
    $sysPass = \TheFramework\App\Config::get('SYSTEM_AUTH_PASS');

    if (!empty($sysUser) && !empty($sysPass)) {
        $authUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $authPass = $_SERVER['PHP_AUTH_PW'] ?? '';

        // FIX: Handle Apache/FastCGI where PHP_AUTH_USER might be missing
        if (empty($authUser) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $decoded = base64_decode($matches[1]);
                if (strpos($decoded, ':') !== false) {
                    list($authUser, $authPass) = explode(':', $decoded, 2);
                }
            }
        }

        $validUser = hash_equals($sysUser, $authUser);

        // Support both plain text and bcrypt
        if (strpos($sysPass, '$2y$') === 0 || strpos($sysPass, '$2a$') === 0) {
            $validPass = password_verify($authPass, $sysPass);
        } else {
            $validPass = hash_equals($sysPass, $authPass);
        }

        if (!$validUser || !$validPass) {
            header('WWW-Authenticate: Basic realm="System Administration Panel"');
            header('HTTP/1.0 401 Unauthorized');
            die("⛔ AUTHENTICATION REQUIRED: Please login to access system tools.");
        }
    }

    // Log successful access
    \TheFramework\App\Logging::getLogger()->info(
        "System route accessed successfully",
        ['ip' => $clientIp, 'uri' => $_SERVER['REQUEST_URI'] ?? '']
    );
}

// 1. MIGRATE DATABASE
Router::add('GET', '/_system/migrate', function () {
    checkSystemKey();

    header('Content-Type: text/plain');
    echo "⚙️ SYSTEM MIGRATION TOOL\n==============================\n";

    try {
        if (!defined('BASE_PATH'))
            define('BASE_PATH', dirname(__DIR__));

        $migrationDir = BASE_PATH . '/database/migrations/';
        $files = glob($migrationDir . '*.php');

        if (empty($files)) {
            echo "ℹ No migration files found.\n";
            return;
        }

        $migrator = new Migrator();
        $migrator->ensureTableExists();
        $ran = $migrator->getRan();

        $pending = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $ran))
                $pending[] = $file;
        }

        if (empty($pending)) {
            echo "✅ Database is up to date.\n";
            return;
        }

        $batch = $migrator->getNextBatchNumber();
        usort($pending, fn($a, $b) => filemtime($a) - filemtime($b));

        foreach ($pending as $file) {
            $baseName = basename($file, '.php');
            require_once $file;
            $class = 'Database\\Migrations\\Migration_' . $baseName;

            if (class_exists($class)) {
                (new $class())->up();
                $migrator->log($baseName, $batch);
                echo "✔ Migrated: $baseName\n";
            } else {
                echo "⚠ Skipped: Class $class not found.\n";
            }
        }
        echo "\n✨ Migration Completed!";

    } catch (\Throwable $e) {
        echo "\n❌ ERROR: " . $e->getMessage();
    }
});

// 2. SEED DATABASE (Web Seeder)
// 2. SEED DATABASE (Web Seeder)
Router::add('GET', '/_system/seed', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🌱 SYSTEM DATABASE SEEDER\n==============================\n";

    try {
        if (!defined('BASE_PATH'))
            define('BASE_PATH', dirname(__DIR__));

        $seedersPath = BASE_PATH . '/database/seeders';
        $files = glob($seedersPath . '/*Seeder.php');

        // Sort files to ensure execution order (timestamps)
        usort($files, function ($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        if (empty($files)) {
            echo "ℹ No seeder files found in database/seeders.\n";
            return;
        }

        foreach ($files as $file) {
            $fileName = basename($file, '.php');

            // Baca isi file untuk cari nama class yang sebenarnya (RegEx)
            // Ini menangani kasus nama file tidak sama persis dengan nama class
            $content = file_get_contents($file);
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = 'Database\\Seeders\\' . $matches[1];
            } else {
                echo "⚠ Skipped: Could not detect class name in $fileName\n";
                continue;
            }

            require_once $file;

            if (class_exists($className)) {
                $seeder = new $className();
                if (method_exists($seeder, 'run')) {
                    $seeder->run();
                    echo "✔ Seeded: $fileName\n";
                } else {
                    echo "⚠ Skipped: Method 'run' missing in $className\n";
                }
            } else {
                echo "⚠ Skipped: Class $className not found.\n";
            }
        }

        echo "\n✨ Database Seeding Completed!";

    } catch (\Throwable $e) {
        echo "\n❌ SEEDING ERROR: " . $e->getMessage();
    }
});

// 2. CLEAR CACHE (Simulasi php artisan config:clear / view:clear)
// Di shared hosting kita sering perlu hapus file cache manual
Router::add('GET', '/_system/clear-cache', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🧹 SYSTEM CACHE CLEANER\n==============================\n";

    $cacheDirs = [
        BASE_PATH . '/storage/framework/views',
        BASE_PATH . '/storage/framework/cache',
        BASE_PATH . '/storage/logs' // Opsional: clear logs
    ];

    foreach ($cacheDirs as $dir) {
        if (!is_dir($dir))
            continue;

        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
                echo "Deleted: " . basename($file) . "\n";
            }
        }
    }
    echo "\n✨ Cache Cleared!";
});

// 3. STORAGE LINK (Simulasi php artisan storage:link)
// Di Linux/Hosting yang support symlink. Windows mungkin butuh admin permission.
Router::add('GET', '/_system/storage-link', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🔗 STORAGE LINKER\n==============================\n";

    $target = BASE_PATH . '/storage/app/public';
    $link = BASE_PATH . '/public/storage';

    if (!is_dir($target)) {
        if (!mkdir($target, 0777, true)) {
            echo "❌ Target directory does not exist and could not be created: $target\n";
            return;
        }
    }

    if (file_exists($link)) {
        echo "ℹ Symlink already exists.\n";
    } else {
        try {
            if (!function_exists('symlink')) {
                throw new \Exception("Function 'symlink' is disabled on this server.");
            }
            if (@symlink($target, $link)) {
                echo "✅ Symlink created: public/storage -> storage/app/public\n";
            } else {
                $error = error_get_last();
                throw new \Exception($error['message'] ?? "Unknown error during symlink creation.");
            }
        } catch (\Throwable $e) {
            echo "❌ Failed to create symlink: " . $e->getMessage() . "\n";
            echo "   (Note: InfinityFree and some shared hosting block symlink creation for security reasons)\n";
            echo "   (Manual Fix: You might need to move your uploads to a public folder instead)\n";
        }
    }
});

// 4. ENVIRONMENT INFO (Cek PHP Version & Extension Wajib)
// 5. FILE HEALTH CHECK (Verify uploads)
Router::add('GET', '/_system/check-files', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🔍 FILE SYSTEM HEALTH CHECK\n==============================\n";

    $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
    $checkPaths = [
        'index.php',
        'bootstrap/app.php',
        'routes/web.php',
        'resources/views/interface/welcome.blade.php',
        'resources/views/errors/exception.blade.php',
        'storage/framework/views/.gitignore'
    ];

    echo "CRITICAL FILES:\n";
    foreach ($checkPaths as $path) {
        $fullPath = $root . '/' . $path;
        $exists = file_exists($fullPath) ? "✅ FOUND" : "❌ MISSING";
        echo str_pad($path, 45) . ": $exists\n";
    }

    echo "\nDIRECTORY SCAN (resources/views):\n";
    $viewDir = $root . '/resources/views';
    if (is_dir($viewDir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewDir));
        $count = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                echo " - " . str_replace($viewDir, '', $file->getPathname()) . "\n";
                $count++;
            }
        }
        echo "\nTotal view files: $count\n";
    } else {
        echo "❌ Directory 'resources/views' NOT FOUND!\n";
    }
});

// 0. WHAT'S MY IP (Public - to help identify IP for whitelisting)
Router::add('GET', '/_system/my-ip', function () {
    header('Content-Type: text/plain');
    $ip = \TheFramework\Helpers\Helper::get_client_ip();
    echo "🌐 YOUR CURRENT IP ADDRESS:\n==============================\n";
    echo $ip . "\n\n";
    echo "Note: Use this IP to update SYSTEM_ALLOWED_IPS in your .env or GitHub Secrets.";
});

Router::add('GET', '/_system/status', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "📊 SYSTEM STATUS\n==============================\n";

    echo "PHP Version: " . phpversion() . "\n";
    echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n\n";

    $required = ['pdo_mysql', 'mbstring', 'openssl', 'json', 'ctype', 'xml'];
    foreach ($required as $ext) {
        $status = extension_loaded($ext) ? "OK" : "MISSING";
        echo str_pad($ext, 15) . ": $status\n";
    }
});

// NEW: Debug Session & Database for InfinityFree Issues
Router::add('GET', '/_system/diagnose', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🔧 SYSTEM DIAGNOSIS\n==============================\n\n";

    // 1. Session Check
    echo "SESSION STATUS:\n";
    echo str_pad("Session Status", 25) . ": " . (session_status() === PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ INACTIVE") . "\n";
    echo str_pad("Session ID", 25) . ": " . (session_id() ?: "N/A") . "\n";
    echo str_pad("Session Save Path", 25) . ": " . (session_save_path() ?: "DEFAULT") . "\n";

    $sessionPath = defined('ROOT_DIR') ? ROOT_DIR . '/storage/session' : dirname(__DIR__) . '/storage/session';
    echo str_pad("Custom Session Dir", 25) . ": " . $sessionPath . "\n";
    echo str_pad("  - Exists", 25) . ": " . (is_dir($sessionPath) ? "✅ YES" : "❌ NO") . "\n";
    echo str_pad("  - Writable", 25) . ": " . (is_writable($sessionPath) ? "✅ YES" : "❌ NO") . "\n";

    echo str_pad("CSRF Token Set", 25) . ": " . (isset($_SESSION['csrf_token']) ? "✅ YES (len:" . strlen($_SESSION['csrf_token']) . ")" : "❌ NO") . "\n";

    echo "\n";

    // 2. Database Check
    echo "DATABASE STATUS:\n";
    try {
        $dbEnabled = \TheFramework\App\Database::isEnabled();
        echo str_pad("DB Enabled", 25) . ": " . ($dbEnabled ? "✅ YES" : "❌ NO") . "\n";

        if ($dbEnabled) {
            $db = \TheFramework\App\Database::getInstance();
            $connected = $db->testConnection();
            echo str_pad("DB Connection", 25) . ": " . ($connected ? "✅ CONNECTED" : "❌ FAILED") . "\n";

            if ($connected) {
                // Test simple query
                $db->query("SELECT 1 as test");
                $result = $db->single();
                echo str_pad("DB Query Test", 25) . ": " . ($result ? "✅ OK" : "❌ FAILED") . "\n";
            }
        }
    } catch (\Throwable $e) {
        echo str_pad("DB Error", 25) . ": " . $e->getMessage() . "\n";
    }

    echo "\n";

    // 3. Storage Directories
    echo "STORAGE DIRECTORIES:\n";
    $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
    $storageDirs = [
        '/storage/session',
        '/storage/logs',
        '/storage/framework/views',
        '/storage/framework/cache',
        '/storage/app/public',
    ];

    foreach ($storageDirs as $dir) {
        $fullPath = $root . $dir;
        $exists = is_dir($fullPath);
        $writable = is_writable($fullPath);
        $status = $exists ? ($writable ? "✅ OK" : "⚠ NOT WRITABLE") : "❌ MISSING";
        echo str_pad($dir, 30) . ": $status\n";
    }

    echo "\n";

    // 4. Request Headers (untuk debug AJAX)
    echo "REQUEST INFO:\n";
    echo str_pad("HTTP Method", 25) . ": " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo str_pad("Content-Type", 25) . ": " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A') . "\n";
    echo str_pad("Accept", 25) . ": " . ($_SERVER['HTTP_ACCEPT'] ?? 'N/A') . "\n";
    echo str_pad("X-CSRF-TOKEN", 25) . ": " . (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? "Present" : "Not sent") . "\n";
    echo str_pad("Cookie Header", 25) . ": " . (isset($_SERVER['HTTP_COOKIE']) ? "Present" : "Not sent") . "\n";

    echo "\n✨ Diagnosis Complete!";
});

// NEW: Optimize (Clear cache & Compiled files)
Router::add('GET', '/_system/optimize', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🚀 SYSTEM OPTIMIZER\n==============================\n";

    $cleared = 0;
    $cacheDirs = [
        BASE_PATH . '/storage/framework/views',
        BASE_PATH . '/storage/framework/cache',
    ];

    foreach ($cacheDirs as $dir) {
        if (!is_dir($dir))
            continue;
        $files = glob($dir . '/*.php');
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
                echo "Cleared: " . basename($file) . "\n";
            }
        }
    }

    // Attempt to clear OpCache
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        echo "✅ OpCache cleared.\n";
    }

    echo "\n✨ Total compiled files cleared: $cleared\n";
    echo "Optimization complete!";
});

// NEW: View Logs
Router::add('GET', '/_system/logs', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "📜 SYSTEM LOGS (Last 50 lines)\n==============================\n";

    $logFile = BASE_PATH . '/storage/logs/app.log';
    if (!file_exists($logFile)) {
        echo "ℹ No log file found at: $logFile\n";
        return;
    }

    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo implode("", $lastLines);
});

// NEW: List Routes
Router::add('GET', '/_system/routes', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🛣️ REGISTERED ROUTES\n==============================\n";

    $routes = Router::getRoutes();
    foreach ($routes as $route) {
        echo str_pad($route['method'], 8) . " : " . $route['path'] . "\n";
    }
});

// NEW: PHP Info
Router::add('GET', '/_system/phpinfo', function () {
    checkSystemKey();
    phpinfo();
});

// NEW: Database Test Connection Details
Router::add('GET', '/_system/test-connection', function () {
    checkSystemKey();
    header('Content-Type: text/plain');
    echo "🔌 DATABASE CONNECTION TEST\n==============================\n";

    try {
        $db = \TheFramework\App\Database::getInstance();
        $start = microtime(true);
        $connected = $db->testConnection();
        $end = microtime(true);

        if ($connected) {
            echo "✅ Status: CONNECTED\n";
            echo "⏱️ Time Taken: " . round(($end - $start) * 1000, 2) . " ms\n";

            // Get Server Info
            $db->query("SELECT VERSION() as version, DATABASE() as db_name");
            $info = $db->single();
            echo "📦 MySQL Version: " . $info['version'] . "\n";
            echo "📂 Database Name: " . $info['db_name'] . "\n";
        } else {
            echo "❌ Status: FAILED\n";
        }
    } catch (\Throwable $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
});

// NEW: System Health
Router::add('GET', '/_system/health', function () {
    header('Content-Type: application/json');
    $dbConnected = false;
    try {
        $dbConnected = \TheFramework\App\Database::getInstance()->testConnection();
    } catch (\Throwable $e) {
    }

    echo json_encode([
        'status' => 'up',
        'php_version' => PHP_VERSION,
        'database' => $dbConnected ? 'connected' : 'disconnected',
        'storage' => is_writable(BASE_PATH . '/storage') ? 'writable' : 'not writable',
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
});



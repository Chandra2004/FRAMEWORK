<?php

use TheFramework\App\Router;
use TheFramework\App\Migrator;
use TheFramework\App\Container;
use TheFramework\Http\Controllers\Services\SitemapController;
use TheFramework\Middleware\WAFMiddleware;

/**
 * Multi-Layer Security Check for System Routes
 * v5.1.0 Security Enhancement
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__));
}

// 15. SITEMAP XML (Automatic)
Router::add('GET', '/sitemap.xml', SitemapController::class, 'index', [WAFMiddleware::class]);

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

/**
 * Helper to render terminal output consistently
 */
function renderTerminal($command, $callback)
{
    ob_start();
    $success = true;
    try {
        $callback();
    } catch (\Throwable $e) {
        $success = false;
        echo "\n❌ FATAL ERROR: " . $e->getMessage();
    }
    $output = ob_get_clean();
    return \TheFramework\App\View::render('Internal::_system.terminal_output', [
        'command' => $command,
        'output' => $output,
        'success' => $success
    ]);
}

// 0. SYSTEM DASHBOARD (Main Entry Point)
Router::add('GET', '/_system', function () {
    checkSystemKey();
    return \TheFramework\App\View::render('Internal::_system.dashboard');
});

// 1. MIGRATE DATABASE
Router::add('GET', '/_system/migrate', function () {
    checkSystemKey();
    return renderTerminal('migrate', function () {
        echo "⚙️ SYSTEM MIGRATION TOOL\n==============================\n";
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
    });
});

// 1.b MIGRATE ROLLBACK
Router::add('GET', '/_system/migrate/rollback', function () {
    checkSystemKey();
    return renderTerminal('migrate:rollback', function () {
        echo "⏪ SYSTEM MIGRATION ROLLBACK\n==============================\n";
        $migrator = new Migrator();
        $migrations = $migrator->getLastBatch();

        if (empty($migrations)) {
            echo "ℹ No migrations to rollback.\n";
            return;
        }

        foreach ($migrations as $migration) {
            $file = BASE_PATH . '/database/migrations/' . $migration . '.php';
            if (file_exists($file)) {
                require_once $file;
                // Fix namespace detection if simple class name
                $baseName = basename($file, '.php');
                $class = 'Database\\Migrations\\Migration_' . $baseName;

                if (class_exists($class)) {
                    (new $class())->down();
                    $migrator->delete($migration);
                    echo "✔ Rolled back: $migration\n";
                } else {
                    echo "⚠ Skipped: Class $class not found.\n";
                }
            } else {
                echo "❌ File missing: $migration.php\n";
            }
        }
        echo "\n✨ Rollback Completed!";
    });
});

// 1.c MIGRATE FRESH
Router::add('GET', '/_system/migrate/fresh', function () {
    checkSystemKey();
    return renderTerminal('migrate:fresh', function () {
        echo "☢️ SYSTEM MIGRATION FRESH (DROP ALL)\n==============================\n";
        $migrator = new Migrator();

        echo "Dropping all tables...\n";
        $migrator->dropAllTables();
        echo "✔ All tables dropped.\n\n";

        // Call standard migrate script logic
        $migrationDir = BASE_PATH . '/database/migrations/';
        $files = glob($migrationDir . '*.php');
        $migrator->ensureTableExists(); // Re-create migrations table

        if (empty($files)) {
            echo "ℹ No migration files found to re-run.\n";
            return;
        }

        $batch = 1; // Start fresh
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        foreach ($files as $file) {
            $baseName = basename($file, '.php');
            require_once $file;
            $class = 'Database\\Migrations\\Migration_' . $baseName;

            if (class_exists($class)) {
                (new $class())->up();
                $migrator->log($baseName, $batch);
                echo "✔ Migrated: $baseName\n";
            }
        }
        echo "\n✨ Fresh Migration Completed!";
    });
});

// 1.d ASSET PUBLISH
Router::add('GET', '/_system/asset-publish', function () {
    checkSystemKey();
    return renderTerminal('asset:publish', function () {
        echo "📦 ASSET PUBLISHER\n==============================\n";
        $src = BASE_PATH . '/resources';
        $dest = BASE_PATH . '/public/assets';
        $folders = ['css', 'js', 'fonts', 'images'];

        foreach ($folders as $folder) {
            $s = "$src/$folder";
            $d = "$dest/$folder";

            if (!is_dir($s))
                continue;

            if (!is_dir($d))
                mkdir($d, 0755, true);

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($s, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $sub = $iterator->getSubPathName();
                if ($item->isDir()) {
                    if (!is_dir("$d/$sub"))
                        mkdir("$d/$sub");
                } else {
                    copy($item, "$d/$sub");
                }
            }
            echo "✔ Published: $folder\n";
        }
        echo "\n✨ Assets successfully published to public/assets!";
    });
});

// 2. SEED DATABASE (Web Seeder)
Router::add('GET', '/_system/seed', function () {
    checkSystemKey();
    return renderTerminal('db:seed', function () {
        echo "🌱 SYSTEM DATABASE SEEDER\n==============================\n";
        $seedersPath = BASE_PATH . '/database/seeders';
        $files = glob($seedersPath . '/*Seeder.php');

        usort($files, function ($a, $b) {
            return strcmp(basename($a), basename($b));
        });

        if (empty($files)) {
            echo "ℹ No seeder files found in database/seeders.\n";
            return;
        }

        foreach ($files as $file) {
            $fileName = basename($file, '.php');
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
    });
});

// 3. CLEAR CACHE
Router::add('GET', '/_system/clear-cache', function () {
    checkSystemKey();
    return renderTerminal('cache:clear', function () {
        echo "🧹 SYSTEM CACHE CLEANER\n==============================\n";
        $cacheDirs = [
            BASE_PATH . '/storage/framework/views',
            BASE_PATH . '/storage/framework/cache',
            BASE_PATH . '/storage/logs'
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
});

// 4. STORAGE LINK
Router::add('GET', '/_system/storage-link', function () {
    checkSystemKey();
    return renderTerminal('storage:link', function () {
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
            if (!function_exists('symlink')) {
                throw new \Exception("Function 'symlink' is disabled on this server.");
            }
            if (@symlink($target, $link)) {
                echo "✅ Symlink created: public/storage -> storage/app/public\n";
            } else {
                $error = error_get_last();
                throw new \Exception($error['message'] ?? "Unknown error during symlink creation.");
            }
        }
    });
});

// 5. FILE HEALTH CHECK
Router::add('GET', '/_system/check-files', function () {
    checkSystemKey();
    return renderTerminal('check-files', function () {
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
});

// 6. WHAT'S MY IP
Router::add('GET', '/_system/my-ip', function () {
    return renderTerminal('my-ip', function () {
        $ip = \TheFramework\Helpers\Helper::get_client_ip();
        echo "🌐 YOUR CURRENT IP ADDRESS:\n==============================\n";
        echo $ip . "\n\n";
        echo "Note: Use this IP to update SYSTEM_ALLOWED_IPS in your .env or GitHub Secrets.";
    });
});

// 7. SYSTEM STATUS
Router::add('GET', '/_system/status', function () {
    checkSystemKey();

    $required = ['pdo_mysql', 'mbstring', 'openssl', 'json', 'ctype', 'xml', 'curl', 'gd'];
    $extensions = [];
    foreach ($required as $ext) {
        $extensions[$ext] = extension_loaded($ext);
    }

    return \TheFramework\App\View::render('Internal::_system.status', [
        'php_version' => phpversion(),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'extensions' => $extensions,
        'memory_limit' => ini_get('memory_limit'),
        'upload_max' => ini_get('upload_max_filesize'),
        'post_max' => ini_get('post_max_size')
    ]);
});

// 8. SYSTEM DIAGNOSIS
Router::add('GET', '/_system/diagnose', function () {
    checkSystemKey();
    return renderTerminal('diagnose', function () {
        echo "🔧 SYSTEM DIAGNOSIS\n==============================\n\n";

        echo "SESSION STATUS:\n";
        echo str_pad("Session Status", 25) . ": " . (session_status() === PHP_SESSION_ACTIVE ? "✅ ACTIVE" : "❌ INACTIVE") . "\n";
        echo str_pad("Session ID", 25) . ": " . (session_id() ?: "N/A") . "\n";
        echo str_pad("Session Save Path", 25) . ": " . (session_save_path() ?: "DEFAULT") . "\n";

        $sessionPath = defined('ROOT_DIR') ? ROOT_DIR . '/storage/session' : dirname(__DIR__) . '/storage/session';
        echo str_pad("Custom Session Dir", 25) . ": " . $sessionPath . "\n";
        echo str_pad("  - Exists", 25) . ": " . (is_dir($sessionPath) ? "✅ YES" : "❌ NO") . "\n";
        echo str_pad("  - Writable", 25) . ": " . (is_writable($sessionPath) ? "✅ YES" : "❌ NO") . "\n";
        echo str_pad("CSRF Token Set", 25) . ": " . (isset($_SESSION['csrf_token']) ? "✅ YES" : "❌ NO") . "\n\n";

        echo "DATABASE STATUS:\n";
        try {
            if (\TheFramework\App\Database::isEnabled()) {
                $db = \TheFramework\App\Database::getInstance();
                echo str_pad("DB Connection", 25) . ": " . ($db->testConnection() ? "✅ CONNECTED" : "❌ FAILED") . "\n";
            }
        } catch (\Throwable $e) {
            echo str_pad("DB Error", 25) . ": " . $e->getMessage() . "\n";
        }

        echo "\nSTORAGE DIRECTORIES:\n";
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
        foreach (['/storage/session', '/storage/logs', '/storage/framework/views'] as $dir) {
            $fullPath = $root . $dir;
            $status = is_dir($fullPath) ? (is_writable($fullPath) ? "✅ OK" : "⚠ NOT WRITABLE") : "❌ MISSING";
            echo str_pad($dir, 25) . ": $status\n";
        }
    });
});

// 9. LOGS
Router::add('GET', '/_system/logs', function () {
    checkSystemKey();

    $logFile = BASE_PATH . '/storage/logs/app.log';
    $logs = [];

    if (file_exists($logFile)) {
        // Read last 100 lines efficiently
        $file = new \SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        $startLine = max(0, $totalLines - 100);

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logs[] = $line;
            }
            $file->next();
        }
        $logs = array_reverse($logs); // Show newest first
    }

    return \TheFramework\App\View::render('Internal::_system.logs', [
        'logs' => $logs,
        'path' => $logFile
    ]);
});

// 10. OPTIMIZE
Router::add('GET', '/_system/optimize', function () {
    checkSystemKey();
    return renderTerminal('optimize', function () {
        echo "🚀 SYSTEM OPTIMIZER\n==============================\n";
        $cleared = 0;
        foreach ([BASE_PATH . '/storage/framework/views', BASE_PATH . '/storage/framework/cache'] as $dir) {
            if (!is_dir($dir))
                continue;
            foreach (glob($dir . '/*.php') as $file) {
                if (@unlink($file)) {
                    $cleared++;
                    echo "Cleared: " . basename($file) . "\n";
                }
            }
        }
        if (function_exists('opcache_reset'))
            @opcache_reset();
        echo "\n✨ Total compiled files cleared: $cleared\n";
    });
});

// 11. ROUTES
Router::add('GET', '/_system/routes', function () {
    checkSystemKey();

    $rawRoutes = Router::getRoutes();
    $routes = [];

    foreach ($rawRoutes as $r) {
        $handlerStr = 'Closure';
        if (is_string($r['handler'])) {
            $handlerStr = $r['handler'] . '@' . ($r['function'] ?? 'index');
        } elseif (is_array($r['handler'])) {
            $handlerStr = 'Array Controller';
        }

        // Detect Type
        $type = 'App';
        if (strpos($r['path'], '/_system') === 0)
            $type = 'System';
        elseif (strpos($r['path'], '/file/') === 0)
            $type = 'Asset';

        $routes[] = [
            'method' => $r['method'],
            'uri' => $r['path'],
            'handler' => $handlerStr,
            'middleware' => implode(', ', array_map(fn($m) => is_string($m) ? basename(str_replace('\\', '/', $m)) : 'Closure', $r['middleware'])),
            'type' => $type
        ];
    }

    return \TheFramework\App\View::render('Internal::_system.routes', [
        'routes' => $routes
    ]);
});

// 12. PHPINFO
Router::add('GET', '/_system/phpinfo', function () {
    checkSystemKey();
    if (!function_exists('phpinfo')) {
        echo "⛔ phpinfo() is disabled on this server.";
        return;
    }
    phpinfo();
});

// 13. TEST CONNECTION DETAILS
Router::add('GET', '/_system/test-connection', function () {
    checkSystemKey();
    return renderTerminal('db:test', function () {
        echo "🔌 DATABASE CONNECTION TEST\n==============================\n";
        $db = \TheFramework\App\Database::getInstance();
        $start = microtime(true);
        if ($db->testConnection()) {
            $end = microtime(true);
            echo "✅ Status: CONNECTED\n";
            echo "⏱️ Time Taken: " . round(($end - $start) * 1000, 2) . " ms\n";
            $db->query("SELECT VERSION() as version, DATABASE() as db_name");
            $info = $db->single();
            echo "📦 MySQL Version: " . $info['version'] . "\n";
            echo "📂 Database Name: " . $info['db_name'] . "\n";
        } else {
            echo "❌ Status: FAILED\n";
        }
    });
});

// 14. HEALTH (JSON)
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
        'storage' => @is_writable(BASE_PATH . '/storage') ? 'writable' : 'not writable',
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
});

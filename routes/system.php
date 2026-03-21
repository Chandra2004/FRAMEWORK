<?php

use TheFramework\App\Http\Router;
use TheFramework\App\Schema\Migrator;
use TheFramework\App\Core\Container;
use TheFramework\App\Internal\Controllers\SitemapController;
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

/**
 * Robust Client IP Detection (Proxy Aware)
 */
function getSystemClientIp(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function checkSystemKey()
{
    // === LAYER 0: Config Integrity Check ===
    $isCached = \TheFramework\App\Core\Config::isCached();
    $dbHost = \TheFramework\App\Core\Config::get('DB_HOST');

    // Jika config ter-cache tapi isinya kosong (karena salah deploy), ingatkan user.
    if ($isCached && empty($dbHost)) {
        abort(403, "🚨 CONFIG CACHE DETECTED: Sistem mendeteksi file cache (storage/cache/config.php) kosong. Silakan hapus file tersebut via FTP agar .env terbaca.");
    }

    // === LAYER 1: Feature Toggle ===
    $allowWeb = \TheFramework\App\Core\Config::get('ALLOW_WEB_MIGRATION', 'false');
    if (strtolower((string)$allowWeb) !== 'true') {
        abort(403, "⛔ FEATURE DISABLED: Web migration tools are disabled. Check ALLOW_WEB_MIGRATION in .env");
    }

    // === LAYER 2: IP Whitelist ===
    $clientIp = getSystemClientIp();
    $allowedIps = \TheFramework\App\Core\Config::get('SYSTEM_ALLOWED_IPS', '127.0.0.1');
    $ipWhitelist = array_map('trim', explode(',', $allowedIps));

    if (!in_array($clientIp, $ipWhitelist) && !in_array('*', $ipWhitelist)) {
        \TheFramework\App\Core\Logging::getLogger()->warning(
            "System route access denied for IP: $clientIp",
            ['uri' => $_SERVER['REQUEST_URI'] ?? '']
        );
        abort(403, "⛔ ACCESS DENIED: Your IP ($clientIp) is not whitelisted for system access. Add it to SYSTEM_ALLOWED_IPS in .env");
    }

    // === LAYER 3: Basic Auth (Required if configured) ===
    $sysUser = \TheFramework\App\Core\Config::get('SYSTEM_AUTH_USER');
    $sysPass = \TheFramework\App\Core\Config::get('SYSTEM_AUTH_PASS');

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

        $validUser = hash_equals((string)$sysUser, (string)$authUser);

        // Support both plain text and bcrypt
        $sysPassStr = (string)$sysPass;
        if (strpos($sysPassStr, '$2y$') === 0 || strpos($sysPassStr, '$2a$') === 0) {
            $validPass = password_verify($authPass, $sysPassStr);
        } else {
            $validPass = hash_equals($sysPassStr, (string)$authPass);
        }

        if (!$validUser || !$validPass) {
            header('WWW-Authenticate: Basic realm="System Administration Panel"');
            abort(401, "⛔ AUTHENTICATION REQUIRED: Please login to access system tools.");
        }
    }

    // Log successful access
    \TheFramework\App\Core\Logging::getLogger()->info(
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
    return \TheFramework\App\Http\View::render('Internal::_system.terminal_output', [
        'command' => $command,
        'output' => $output,
        'success' => $success
    ]);
}

// 0. SYSTEM DASHBOARD (Main Entry Point)
Router::add('GET', '/_system', function () {
    checkSystemKey();
    return \TheFramework\App\Http\View::render('Internal::_system.dashboard');
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
        
        // Safe Clear Uploads (Sync Engine)
        echo "\n\n🧹 Clearing allowed upload folders...\n";
        $fileCount = \TheFramework\Helpers\Helper::clear_uploads();
        echo "✔ $fileCount files/folders removed from allowed directories.\n";
        
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

        // Safe Clear Uploads (Sync Engine)
        echo "\n\n🧹 Clearing allowed upload folders...\n";
        $fileCount = \TheFramework\Helpers\Helper::clear_uploads();
        echo "✔ $fileCount files/folders removed from allowed directories.\n";

        echo "\n✨ Fresh Migration Completed!";
    });
});

// 1.c.2 MIGRATE STATUS
Router::add('GET', '/_system/migrate/status', function () {
    checkSystemKey();
    return renderTerminal('migrate:status', function () {
        echo "📊 DATABASE MIGRATION STATUS\n==============================\n\n";
        $migrator = new Migrator();
        $migrator->ensureTableExists();
        $ran = $migrator->getRan();
        
        $migrationDir = BASE_PATH . '/database/migrations/';
        $files = glob($migrationDir . '*.php');
        
        if (empty($files)) {
            echo "ℹ No migration files found.\n";
            return;
        }

        printf("%-10s | %-50s\n", "Status", "Migration Name");
        echo str_repeat("-", 65) . "\n";

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $status = in_array($name, $ran) ? "✅ RAN" : "⏳ PENDING";
            printf("%-10s | %-50s\n", $status, $name);
        }
        echo str_repeat("-", 65) . "\n";
        echo "✨ Total: " . count($files) . " migrations monitored.";
    });
});

// 1.d ASSET PUBLISH
Router::add('GET', '/_system/asset/publish', function () {
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
                /** @var \RecursiveDirectoryIterator $innerIterator */
                $innerIterator = $iterator->getInnerIterator();
                $sub = $innerIterator->getSubPathName();

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

// 1.e DATABASE SCHEMA INSPECTOR (Paten Feature)
Router::add('GET', '/_system/schema', function () {
    checkSystemKey();
    return renderTerminal('db:schema', function () {
        echo "🔍 DATABASE SCHEMA INSPECTOR\n==============================\n";
        $db = \TheFramework\App\Database\Database::getInstance();

        // Get Tables
        $tables = array_map(fn($row) => array_values((array) $row)[0] ?? '', $db->query("SHOW TABLES")->resultSet());

        if (empty($tables)) {
            echo "ℹ No tables found in the database.\n";
            return;
        }

        echo "Found " . count($tables) . " tables:\n\n";
        printf("%-30s | %-10s\n", "Table Name", "Rows");
        echo str_repeat("-", 45) . "\n";

        foreach ($tables as $table) {
            // Validasi nama tabel hanya alphanumeric + underscore untuk mencegah SQLi/Break
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$table)) {
                printf("%-30s | %-10s\n", substr((string)$table, 0, 30), "SKIPPED");
                continue;
            }
            $count = $db->query("SELECT COUNT(*) as total FROM `{$table}`")->single();
            printf("%-30s | %-10d\n", $table, $count['total'] ?? 0);
        }

        echo str_repeat("-", 45) . "\n";
        echo "✨ Schema scan completed!";
    });
});

// 1.f ENVIRONMENT
Router::add('GET', '/_system/env', function () {
    checkSystemKey();
    return renderTerminal('env', function () {
        echo "🌍 APPLICATION ENVIRONMENT\n==============================\n\n";
        $env = \TheFramework\App\Core\Config::get('APP_ENV', 'local');
        $debug = \TheFramework\App\Core\Config::get('APP_DEBUG', 'false');
        $host = \TheFramework\App\Core\Config::get('DB_HOST', 'N/A');
        
        echo "CURRENT_ENV : " . strtoupper((string)$env) . "\n";
        echo "APP_DEBUG   : " . (strtolower((string)$debug) === 'true' ? "✅ ENABLED" : "❌ DISABLED") . "\n";
        echo "DB_HOST     : " . $host . "\n";
        echo "APP_URL     : " . \TheFramework\App\Core\Config::get('APP_URL', 'http://localhost') . "\n";
        echo "\n✨ Configuration snapshot completed.";
    });
});

// 1.g MAINTENANCE DOWN
Router::add('GET', '/_system/maintenance/down', function () {
    checkSystemKey();
    return renderTerminal('down', function () {
        echo "🚧 ENABLING MAINTENANCE MODE\n==============================\n";
        $file = BASE_PATH . '/storage/framework/down';
        if (file_put_contents($file, json_encode([
            'time' => time(),
            'message' => 'System is under maintenance',
            'allowed' => [\TheFramework\App\Core\Config::get('SYSTEM_ALLOWED_IPS', '127.0.0.1')]
        ]))) {
            echo "✅ Maintenance mode is now ACTIVE.\n";
            echo "ℹ  The site will return 503 for non-whitelisted IPs.\n";
        } else {
            echo "❌ Failed to create maintenance file.\n";
        }
    });
});

// 1.h MAINTENANCE UP
Router::add('GET', '/_system/maintenance/up', function () {
    checkSystemKey();
    return renderTerminal('up', function () {
        echo "🚢 DISABLING MAINTENANCE MODE\n==============================\n";
        $file = BASE_PATH . '/storage/framework/down';
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "✅ Maintenance mode is now INACTIVE.\n";
                echo "🚀  The application is back online!\n";
            } else {
                echo "❌ Failed to remove maintenance file.\n";
            }
        } else {
            echo "ℹ Website was already online.\n";
        }
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

// 3. CACHE CLEAR
Router::add('GET', '/_system/cache/clear', function () {
    checkSystemKey();
    return renderTerminal('cache:clear', function () {
        echo "🧹 SYSTEM CACHE CLEANER\n==============================\n";
        $cacheDirs = [
            BASE_PATH . '/storage/framework/cache',
            BASE_PATH . '/storage/cache'
        ];

        foreach ($cacheDirs as $dir) {
            if (!is_dir($dir))
                continue;
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    unlink($file);
                    echo "✔ Deleted: " . basename($file) . "\n";
                }
            }
        }
        echo "\n✨ Application cache cleared!";
    });
});

// 4. STORAGE LINK
Router::add('GET', '/_system/storage/link', function () {
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

// 5.b TEST RUNNER
Router::add('GET', '/_system/test', function () {
    checkSystemKey();
    return renderTerminal('test', function () {
        echo "🧪 APPLICATION TEST RUNNER\n==============================\n";
        $phpunit = BASE_PATH . '/vendor/bin/phpunit';
        if (!file_exists($phpunit)) {
            echo "❌ PHPUnit not found in vendor/bin/phpunit\n";
            return;
        }

        echo "Running: vendor/bin/phpunit --testdox\n\n";
        // Use shell_exec to capture output
        $output = shell_exec("php $phpunit --testdox --colors=never");
        echo $output ?: "⚠ No output received from test runner. Check if tests exist in /tests assembly.";
        echo "\n\n✨ Test suite finished.";
    });
});

// 6. WHAT'S MY IP
Router::add('GET', '/_system/my-ip', function () {
    // === SARAN-B4-003: Rate Limit for reconnaissance protection ===
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    \TheFramework\App\Http\RateLimiter::check($ip, 10, 300); // Max 10 checks per 5 min

    return renderTerminal('my-ip', function () use ($ip) {
        echo "🌐 YOUR CURRENT IP ADDRESS:\n==============================\n";
        echo $ip . "\n\n";
        echo "Note: Use this IP to update SYSTEM_ALLOWED_IPS in your .env or GitHub Secrets.";
    });
});

// 7. SYSTEM STATUS (Comprehensive Audit)
Router::add('GET', '/_system/status', function () {
    checkSystemKey();

    // 1. Core Extensions Audit
    $required = ['pdo_mysql', 'mbstring', 'openssl', 'json', 'ctype', 'xml', 'curl', 'gd', 'intl', 'bcmath', 'zip'];
    $extensions = [];
    foreach ($required as $ext) {
        $extensions[$ext] = [
            'enabled' => extension_loaded($ext),
            'version' => extension_loaded($ext) ? phpversion($ext) : 'N/A'
        ];
    }

    // 2. Resource Limits
    $limits = [
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time') . 's',
        'Max Input Vars' => ini_get('max_input_vars'),
        'Upload Max Size' => ini_get('upload_max_filesize'),
        'Post Max Size' => ini_get('post_max_size'),
        'Max Input Time' => ini_get('max_input_time') . 's',
    ];

    // 3. Engine & Server
    $engine = [
        'PHP Version' => PHP_VERSION,
        'SAPI' => php_sapi_name(),
        'OS' => PHP_OS_FAMILY . ' (' . PHP_OS . ')',
        'Zend Engine' => zend_version(),
        'OpCache' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') ? '✅ Enabled' : '❌ Disabled',
        'JIT' => extension_loaded('Zend OPcache') && ini_get('opcache.jit') ? '✅ Active' : '❌ Inactive',
    ];

    // 4. Critical Security & Networking
    $security = [
        'HTTPS' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '✅ Secure' : '⚠️ Insecure',
        'OpenSSL' => OPENSSL_VERSION_TEXT,
        'cURL' => function_exists('curl_version') ? curl_version()['version'] : '❌ Missing',
        'Safe Mode' => ini_get('safe_mode') ? '⚠️ ON' : '✅ OFF',
    ];

    // 5. Disabled Functions (Common on Free Hosting)
    $disabled = explode(',', (string)ini_get('disable_functions'));
    $critical_functions = ['exec', 'shell_exec', 'passthru', 'system', 'popen', 'proc_open'];
    $function_audit = [];
    foreach ($critical_functions as $func) {
        $function_audit[$func] = !in_array($func, array_map('trim', $disabled)) && function_exists($func);
    }

    // 6. Path Permissions
    $paths = [
        'Root' => BASE_PATH,
        'Storage' => BASE_PATH . '/storage',
        'Logs' => BASE_PATH . '/storage/logs',
        'Cache' => BASE_PATH . '/storage/framework/cache'
    ];
    $path_audit = [];
    foreach ($paths as $label => $path) {
        $path_audit[$label] = [
            'exists' => is_dir($path),
            'writable' => is_writable($path)
        ];
    }

    return \TheFramework\App\Http\View::render('Internal::_system.status', [
        'php_version' => phpversion(),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'engine' => $engine,
        'limits' => $limits,
        'extensions' => $extensions,
        'security' => $security,
        'function_audit' => $function_audit,
        'path_audit' => $path_audit,
        'server_time' => date('Y-m-d H:i:s T')
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
            if (\TheFramework\App\Database\Database::isEnabled()) {
                $db = \TheFramework\App\Database\Database::getInstance();
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

// 9. LOGS (Daily & System)
Router::add('GET', '/_system/logs', function () {
    checkSystemKey();

    $logDir = BASE_PATH . '/storage/logs';
    $logFiles = glob($logDir . '/*.log');
    
    // Sort log files by modification time (latest first)
    if (!empty($logFiles)) {
        usort($logFiles, fn($a, $b) => filemtime($b) - filemtime($a));
    }

    // Determine the current file to view
    $selectedFile = $_GET['file'] ?? (!empty($logFiles) ? basename($logFiles[0]) : 'app.log');
    
    // 🛡️ SECURITY: Prevent path traversal (Allow only alphanumeric, dash, underscores, dots, and must end in .log)
    if (!preg_match('/^[a-zA-Z0-9\.\-_]+\.log$/', $selectedFile)) {
        $selectedFile = !empty($logFiles) ? basename($logFiles[0]) : 'app.log';
    }

    $logPath = $logDir . '/' . $selectedFile;
    $logs = [];
    $fileSize = 0;

    if (file_exists($logPath)) {
        $fileSize = filesize($logPath);
        // Read last 200 lines efficiently if the file exists
        if ($fileSize > 0) {
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            $startLine = max(0, $totalLines - 200);

            $file->seek($startLine);
            while (!$file->eof()) {
                $line = trim((string)$file->current());
                if (!empty($line)) {
                    $logs[] = $line;
                }
                $file->next();
            }
            $logs = array_reverse($logs); // Show newest first
        }
    }

    return \TheFramework\App\Http\View::render('Internal::_system.logs', [
        'logs' => $logs,
        'path' => $logPath,
        'all_files' => array_map(function($f) {
            return [
                'name' => basename($f),
                'size' => round(filesize($f) / 1024, 2) . ' KB',
                'modified' => date('Y-m-d H:i', filemtime($f))
            ];
        }, $logFiles),
        'current_file' => $selectedFile,
        'file_size' => round($fileSize / 1024, 2) . ' KB'
    ]);
});

// 10. OPTIMIZE CLEAR
Router::add('GET', '/_system/optimize/clear', function () {
    checkSystemKey();
    return renderTerminal('optimize:clear', function () {
        echo "🚀 SYSTEM HARD OPTIMIZE CLEAR\n==============================\n";
        $cleared = 0;
        $dirs = [
            BASE_PATH . '/storage/framework/views',
            BASE_PATH . '/storage/framework/cache',
            BASE_PATH . '/storage/cache',
            BASE_PATH . '/storage/logs',
            BASE_PATH . '/storage/session'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            foreach (glob($dir . '/*') as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    if (@unlink($file)) {
                        $cleared++;
                    }
                }
            }
            echo "✔ Cleared directory: " . basename($dir) . "\n";
        }

        if (function_exists('opcache_reset')) @opcache_reset();
        echo "\n✨ SEMUA cache, views terkompilasi, log, dan session berhasil dihapus!";
    });
});

// 10.b VIEW CLEAR
Router::add('GET', '/_system/view/clear', function () {
    checkSystemKey();
    return renderTerminal('view:clear', function () {
        echo "🖼️ VIEW CACHE CLEAR\n==============================\n";
        $dir = BASE_PATH . '/storage/framework/views';
        $count = 0;
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.php') as $file) {
                if (@unlink($file)) $count++;
            }
        }
        echo "✔ Total $count compiled views removed.\n";
        echo "\n✨ View cache cleared!";
    });
});

// 10.c CONFIG CACHE
Router::add('GET', '/_system/config/cache', function () {
    checkSystemKey();
    return renderTerminal('config:cache', function () {
        echo "⚙️ CONFIG CACHE GENERATOR\n==============================\n";
        $file = \TheFramework\App\Core\Config::cache();
        echo "✔ Config cached successfully to: " . basename($file) . "\n";
        echo "\n✨ Configuration cached for faster performance!";
    });
});

// 10.d CONFIG CLEAR
Router::add('GET', '/_system/config/clear', function () {
    checkSystemKey();
    return renderTerminal('config:clear', function () {
        echo "⚙️ CONFIG CACHE CLEANER\n==============================\n";
        if (\TheFramework\App\Core\Config::clearCache()) {
            echo "✔ Config cache file removed.\n";
        } else {
            echo "ℹ No config cache file found.\n";
        }
        echo "\n✨ Configuration cache cleared!";
    });
});

// 10.e ROUTE CACHE
Router::add('GET', '/_system/route/cache', function () {
    checkSystemKey();
    return renderTerminal('route:cache', function () {
        echo "📍 ROUTE CACHE GENERATOR\n==============================\n";
        try {
            if (Router::cacheRoutes()) {
                echo "✔ Routes cached successfully.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "Tip: Make sure you don't have closures in routes/web.php or system.php if you want to cache them.\n";
            return;
        }
        echo "\n✨ Route cache generated!";
    });
});

// 10.f ROUTE CLEAR
Router::add('GET', '/_system/route/clear', function () {
    checkSystemKey();
    return renderTerminal('route:clear', function () {
        echo "📍 ROUTE CACHE CLEANER\n==============================\n";
        $cacheFile = BASE_PATH . '/storage/cache/routes.php';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            echo "✔ Route cache file removed.\n";
        } else {
            echo "ℹ No route cache file found.\n";
        }
        echo "\n✨ Route cache cleared!";
    });
});

// 11. ROUTES (Categorized Inventory)
Router::add('GET', '/_system/routes', function () {
    checkSystemKey();

    $rawRoutes = Router::getRoutes();
    
    $categories = [
        'APPLICATION'    => [],
        'FILE STORAGE'   => [],
        'STATIC ASSETS'  => [],
        'SEO & SITEMAP'  => [],
        'SYSTEM CONTROL' => []
    ];

    foreach ($rawRoutes as $r) {
        $handlerStr = 'Closure';
        if (is_string($r['handler'])) {
            $handlerStr = $r['handler'] . '@' . ($r['function'] ?? 'index');
        } elseif (is_array($r['handler'])) {
            $handlerStr = basename(str_replace('\\', '/', $r['handler'][0])) . '@' . ($r['handler'][1] ?? 'index');
        }

        $path = $r['path'];
        $cat = 'APPLICATION';
        
        if (strpos($path, '/_system') === 0) {
            $cat = 'SYSTEM CONTROL';
        } elseif (strpos($path, '/file/') === 0) {
            $cat = 'FILE STORAGE';
        } elseif (strpos($path, '/assets/') === 0) {
            $cat = 'STATIC ASSETS';
        } elseif ($path === '/sitemap.xml') {
            $cat = 'SEO & SITEMAP';
        }

        $categories[$cat][] = [
            'method' => $r['method'],
            'uri' => $path,
            'name' => $r['name'] ?? null,
            'handler' => $handlerStr,
            'middleware' => implode(', ', array_map(fn($m) => is_string($m) ? basename(str_replace('\\', '/', $m)) : 'λ', $r['middleware'])),
            'type' => $cat
        ];
    }

    return \TheFramework\App\Http\View::render('Internal::_system.routes', [
        'categories' => $categories
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
        $db = \TheFramework\App\Database\Database::getInstance();
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

// 14. WEB TINKER
Router::add('GET', '/_system/tinker', function () {
    checkSystemKey();
    return \TheFramework\App\Http\View::render('Internal::_system.tinker');
});

Router::add('POST', '/_system/tinker', function () {
    checkSystemKey();

    // === CSRF PROTECTION VALIDATION ===
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!\TheFramework\Middleware\CsrfMiddleware::verifyToken($token)) {
        return json(['error' => 'Security Error', 'result' => 'CSRF token mismatch'], 403);
    }

    $code = $_POST['code'] ?? '';

    if (trim($code) === '') {
        return json(['output' => '', 'result' => null]);
    }

    // === MILITARY GRADE SECURITY SCANNER (RCE PROTECTION) ===
    // We scan for ANY presence of blocked keywords to prevent concatenation/callback bypasses.
    $blockedKeywords = [
        // Execution
        'system', 'exec', 'shell_exec', 'passthru', 'popen', 'proc_open', 'pcntl_exec', 'eval', 'assert',
        // Filesystem (Direct & Objects)
        'fopen', 'fwrite', 'fputs', 'file_put_contents', 'file_get_contents', 'unlink', 'rmdir', 'mkdir', 
        'chmod', 'chown', 'copy', 'rename', 'SplFileObject', 'SplFileInfo', 'SplTempFileObject',
        // Callbacks & Reflection (Bypass vectors)
        'call_user_func', 'call_user_func_array', 'ReflectionClass', 'ReflectionFunction', 'ReflectionMethod', 
        'ReflectionObject', 'ReflectionProperty', 'create_function', 'preg_replace',
        // Other dangerous
        'include', 'require', 'include_once', 'require_once', 'move_uploaded_file', 'is_uploaded_file'
    ];

    $cleanCode = str_replace([' ', "\n", "\r", "\t", '.', "'", '"'], '', $code); // Strip whitespace & common bypass chars
    
    foreach ($blockedKeywords as $keyword) {
        // Double Check: Direct match in raw code OR match in cleaned code (prevents 'file'.'put'.'contents')
        if (stripos($code, $keyword) !== false || stripos($cleanCode, $keyword) !== false) {
             return json([
                'output' => '', 
                'result' => "🔥 MILITARY SECURITY ALERT: Executing '$keyword' is strictly prohibited. Your IP has been logged."
            ], 403);
        }
    }

    // 1. Auto-Alias Models (Fix Path Detection)
    $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2));
    $modelsDir = $root . '/app/Models';

    // Debug Alias Count
    $aliasCount = 0;

    if (is_dir($modelsDir)) {
        foreach (glob($modelsDir . '/*.php') as $file) {
            $className = basename($file, '.php');
            $fullClassName = "\\TheFramework\\Models\\$className";
            if (class_exists($fullClassName) && !class_exists($className)) {
                class_alias($fullClassName, $className);
                $aliasCount++;
            }
        }
    }

    // 2. Prepare Code
    $code = trim($code);
    if (substr($code, -1) === ';')
        $code = substr($code, 0, -1);

    // Detect type
    $isEcho = preg_match('/^(echo|print|var_dump|print_r)\s/', $code);
    $isAssignment = preg_match('/^\$[a-zA-Z0-9_]+\s*=/', $code);

    if (!$isEcho && !$isAssignment) {
        $evalCode = "return $code;";
    } else {
        $evalCode = "$code;";
    }

    // 3. Execute
    ob_start();
    try {
        $result = eval($evalCode);
        $output = ob_get_clean();

        // Format Result (CLI Standard Alignment)
        $formattedResult = null;
        if (!$isEcho && $result !== null) {
            // Jika object punya method toArray() (seperti Model/Collection), panggil dulu
            $dataToDump = $result;
            if (is_object($result) && method_exists($result, 'toArray')) {
                $dataToDump = $result->toArray();
            }

            // Jika array/object, gunakan JSON Pretty Print biar bersih (mirip CLI)
            if (is_array($dataToDump) || is_object($dataToDump)) {
                $formattedResult = json_encode($dataToDump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } else {
                // Skalar (string/bool/int) tetap pakai print_r biasa
                $formattedResult = print_r($dataToDump, true);
            }

            // Jika hasil terlalu panjang, cegah crash
            if (strlen($formattedResult) > 50000) {
                $formattedResult = substr($formattedResult, 0, 5000) . "\n\n... (Output truncated, too long) ...";
            }
        }

        // Append alias info jika error class not found terjadi (untuk debugging)
        if (strpos($output, 'Class not found') !== false) {
            $output .= "\nDEBUG: $aliasCount models aliased. Script executed in: " . getcwd();
        }

        echo json_encode([
            'output' => $output,
            'result' => $formattedResult
        ]);
    } catch (\Throwable $e) {
        ob_end_clean();
        echo json_encode([
            'output' => '',
            'result' => "🔥 Error: " . $e->getMessage() . " in line " . $e->getLine()
        ]);
    }
});

// 15. HEALTH (JSON)
Router::add('GET', '/_system/health', function () {
    header('Content-Type: application/json');
    $dbConnected = false;
    try {
        $dbConnected = \TheFramework\App\Database\Database::getInstance()->testConnection();
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

// ========================================================
// 16. BACKUP MANAGEMENT
// ========================================================

// 16a. Backup Dashboard
Router::add('GET', '/_system/backup', function () {
    checkSystemKey();

    $databases = [];
    $tables = [];
    $currentDb = '';

    try {
        $db = \TheFramework\App\Database\Database::getInstance();
        if ($db->testConnection()) {
            // Ambil database saat ini
            $currentDb = $_GET['db'] ?? \TheFramework\App\Core\Config::get('DB_NAME', '');

            // List semua database yang bisa diakses
            $db->query("SHOW DATABASES");
            $db->execute();
            $rawDbs = $db->resultSet();
            // resultSet() returns associative arrays, extract first column value
            $allDbs = array_map(fn($row) => array_values((array) $row)[0] ?? '', $rawDbs);
            // Filter system databases
            $systemDbs = ['information_schema', 'mysql', 'performance_schema', 'sys', 'phpmyadmin'];
            $databases = array_values(array_filter($allDbs, fn($d) => !in_array($d, $systemDbs)));

            // Pastikan currentDb valid
            if (empty($currentDb) || !in_array($currentDb, $databases)) {
                $currentDb = $databases[0] ?? '';
            }

            // Ambil info tabel dari database yang dipilih
            if (!empty($currentDb) && preg_match('/^[a-zA-Z0-9_]+$/', $currentDb)) {
                $db->query("SELECT TABLE_NAME, TABLE_ROWS, 
                    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 1) as size_kb
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = :db ORDER BY TABLE_NAME");
                $db->bind(':db', $currentDb);
                $db->execute();
                $rawTables = $db->resultSet();

                foreach ($rawTables as $t) {
                    $sizeKb = (float) ($t['size_kb'] ?? 0);
                    $tables[] = [
                        'name' => $t['TABLE_NAME'],
                        'rows' => (int) ($t['TABLE_ROWS'] ?? 0),
                        'size' => $sizeKb >= 1024
                            ? round($sizeKb / 1024, 1) . ' MB'
                            : round($sizeKb, 1) . ' KB',
                    ];
                }
            }
        }
    } catch (\Throwable $e) {
        // DB not available - page will show error state
    }

    return \TheFramework\App\Http\View::render('Internal::_system.backup', [
        'databases' => $databases,
        'currentDb' => $currentDb,
        'tables' => $tables,
    ]);
});

// 16b. Download Database Backup (.sql)
Router::add('GET', '/_system/backup/database', function () {
    checkSystemKey();

    $dbName = $_GET['db'] ?? \TheFramework\App\Core\Config::get('DB_NAME', '');

    if (empty($dbName) || !preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        abort(400, 'Invalid database name.');
    }

    $timestamp = date('Y-m-d_His');
    $filename = "{$dbName}_backup_{$timestamp}.sql";

    // Set headers untuk download
    header('Content-Type: application/sql');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Coba mysqldump dulu (lebih cepat & reliable)
    $dbHost = \TheFramework\App\Core\Config::get('DB_HOST', '127.0.0.1');
    $dbPort = \TheFramework\App\Core\Config::get('DB_PORT', '3306');
    $dbUser = \TheFramework\App\Core\Config::get('DB_USER', 'root');
    $dbPass = \TheFramework\App\Core\Config::get('DB_PASS', '');

    $mysqldumpPath = 'mysqldump';
    $canUseMysqldump = false;

    // Test apakah mysqldump tersedia
    if (PHP_OS_FAMILY === 'Windows') {
        exec('where mysqldump 2>NUL', $output, $code);
    } else {
        exec('which mysqldump 2>/dev/null', $output, $code);
    }
    $canUseMysqldump = ($code === 0);

    if ($canUseMysqldump) {
        // Gunakan mysqldump via passthru
        $passArg = !empty($dbPass) ? '-p' . escapeshellarg($dbPass) : '';
        $cmd = sprintf(
            '%s -h %s -P %s -u %s %s --single-transaction --routines --triggers --databases %s',
            escapeshellarg($mysqldumpPath),
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            $passArg,
            escapeshellarg($dbName)
        );
        passthru($cmd);
    } else {
        // Fallback: Manual SQL dump via PDO
        $db = \TheFramework\App\Database\Database::getInstance();

        echo "-- ========================================\n";
        echo "-- THE FRAMEWORK Database Backup\n";
        echo "-- Database: {$dbName}\n";
        echo "-- Date: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Server: {$dbHost}:{$dbPort}\n";
        echo "-- ========================================\n\n";
        echo "SET NAMES utf8mb4;\n";
        echo "SET FOREIGN_KEY_CHECKS = 0;\n";
        echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        // Ambil semua tabel
        $db->query("SHOW TABLES FROM `{$dbName}`");
        $db->execute();
        $tables = array_map(fn($row) => array_values((array) $row)[0] ?? '', $db->resultSet());

        foreach ($tables as $table) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$table)) continue;

            // DROP + CREATE TABLE
            echo "-- ----------------------------\n";
            echo "-- Table: {$table}\n";
            echo "-- ----------------------------\n";
            echo "DROP TABLE IF EXISTS `{$table}`;\n";

            $db->query("SHOW CREATE TABLE `{$dbName}`.`{$table}`");
            $db->execute();
            $createRow = $db->single();
            $createSql = $createRow['Create Table'] ?? $createRow['Create View'] ?? '';
            echo "{$createSql};\n\n";

            // INSERT statements
            $db->query("SELECT * FROM `{$dbName}`.`{$table}`");
            $db->execute();
            $rows = $db->resultSet();

            if (!empty($rows)) {
                $columns = array_keys((array) $rows[0]);
                $colNames = '`' . implode('`, `', $columns) . '`';

                // Batch insert (500 per batch)
                $chunks = array_chunk($rows, 500);
                foreach ($chunks as $chunk) {
                    echo "INSERT INTO `{$table}` ({$colNames}) VALUES\n";
                    $valueSets = [];
                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ((array) $row as $val) {
                            if (is_null($val)) {
                                $vals[] = 'NULL';
                            } else {
                                $vals[] = "'" . addslashes((string) $val) . "'";
                            }
                        }
                        $valueSets[] = '(' . implode(', ', $vals) . ')';
                    }
                    echo implode(",\n", $valueSets) . ";\n\n";
                }
            }
            echo "\n";
        }

        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        echo "\n-- ========================================\n";
        echo "-- Backup completed: " . date('Y-m-d H:i:s') . "\n";
        echo "-- ========================================\n";
    }
    exit;
});

// 16c. Download Application Backup (.zip)
Router::add('GET', '/_system/backup/app', function () {
    checkSystemKey();
    generateAppBackup(false);
});

// 16d. Download Full Backup (App + DB) (.zip)
Router::add('GET', '/_system/backup/full', function () {
    checkSystemKey();
    generateAppBackup(true);
});

/**
 * Helper: Generate application ZIP backup
 */
function generateAppBackup(bool $includeDb = false)
{
    if (!class_exists('ZipArchive')) {
        abort(500, 'PHP ZipArchive extension (ext-zip) is required for application backup.');
    }

    $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__));
    $timestamp = date('Y-m-d_His');
    $type = $includeDb ? 'full_backup' : 'app_backup';
    $filename = "theframework_{$type}_{$timestamp}.zip";
    $tempPath = sys_get_temp_dir() . '/' . $filename;

    $zip = new \ZipArchive();
    if ($zip->open($tempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        abort(500, 'Failed to create ZIP archive.');
    }

    // Directories to include
    $includeDirs = ['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'public', 'storage', 'private-uploads', '.idx', '.vscode'];
    // Files to include from root (exact names)
    $includeFiles = ['composer.json', 'composer.lock', 'artisan', 'index.php', '.env', '.htaccess', '.gitignore'];
    // Patterns to exclude (subdirectories or specific files)
    $excludePatterns = [
        'vendor/',
        'node_modules/',
        '.git/',
        'storage/logs/',
        'storage/framework/views/',
        'storage/framework/cache/',
        'storage/session/'
    ];

    // Add root files
    foreach ($includeFiles as $file) {
        $fullPath = $root . '/' . $file;
        if (file_exists($fullPath)) {
            $zip->addFile($fullPath, $file);
        }
    }

    // Add directories recursively
    foreach ($includeDirs as $dir) {
        $dirPath = $root . '/' . ltrim($dir, '/');
        if (!is_dir($dirPath)) continue;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $dir . '/' . substr($item->getPathname(), strlen($dirPath) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            // Check excluded patterns
            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (str_contains($relativePath, $pattern)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($item->isFile()) {
                // Skip files larger than 100MB to prevent timeout
                if ($item->getSize() > 100 * 1024 * 1024) continue;
                $zip->addFile($item->getPathname(), $relativePath);
            }
        }
    }

    // Include database dump if full backup
    if ($includeDb) {
        $dbName = \TheFramework\App\Core\Config::get('DB_NAME', '');
        if (!empty($dbName)) {
            // Generate SQL dump in memory
            ob_start();
            $db = \TheFramework\App\Database\Database::getInstance();

            echo "-- THE FRAMEWORK Full Backup - Database Dump\n";
            echo "-- Database: {$dbName}\n";
            echo "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

            $db->query("SHOW TABLES FROM `{$dbName}`");
            $db->execute();
            $tables = array_map(fn($row) => array_values((array) $row)[0] ?? '', $db->resultSet());

            foreach ($tables as $table) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$table)) continue;

                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                $db->query("SHOW CREATE TABLE `{$dbName}`.`{$table}`");
                $db->execute();
                $createRow = $db->single();
                echo ($createRow['Create Table'] ?? $createRow['Create View'] ?? '') . ";\n\n";

                $db->query("SELECT * FROM `{$dbName}`.`{$table}`");
                $db->execute();
                $rows = $db->resultSet();

                if (!empty($rows)) {
                    $columns = array_keys((array) $rows[0]);
                    $colNames = '`' . implode('`, `', $columns) . '`';
                    $chunks = array_chunk($rows, 500);
                    foreach ($chunks as $chunk) {
                        echo "INSERT INTO `{$table}` ({$colNames}) VALUES\n";
                        $valueSets = [];
                        foreach ($chunk as $row) {
                            $vals = [];
                            foreach ((array) $row as $val) {
                                $vals[] = is_null($val) ? 'NULL' : "'" . addslashes((string) $val) . "'";
                            }
                            $valueSets[] = '(' . implode(', ', $vals) . ')';
                        }
                        echo implode(",\n", $valueSets) . ";\n\n";
                    }
                }
            }
            echo "SET FOREIGN_KEY_CHECKS = 1;\n";

            $sqlDump = ob_get_clean();
            $zip->addFromString("database/{$dbName}_dump.sql", $sqlDump);
        }
    }

    $zip->close();

    // Send file
    header('Content-Type: application/zip');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Content-Length: ' . filesize($tempPath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($tempPath);

    // Cleanup
    @unlink($tempPath);
    exit;
}

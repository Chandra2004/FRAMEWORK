<?php

use TheFramework\App\Router;
use TheFramework\App\Migrator;
use TheFramework\App\Container;

// Helper function to check security key
// Helper function to check security key
function checkSystemKey()
{
    // Feature Toggle Check
    // Harus string 'true' karena env values biasanya string
    if (($_ENV['ALLOW_WEB_MIGRATION'] ?? 'false') !== 'true') {
        header('HTTP/1.0 403 Forbidden');
        die("⛔ FEATURE DISABLED: Web migration tools are disabled in configuration.");
    }

    $key = $_GET['key'] ?? null;
    $appKey = $_ENV['APP_KEY'] ?? 'base64:default';

    // Perbandingan string (Timing Attack Safe tidak terlalu krusial di sini tapi good practice)
    if (!$key || !hash_equals($appKey, $key)) {
        header('HTTP/1.0 403 Forbidden');
        die("⛔ SYSTEM ERROR: Invalid Security Key.");
    }
}

// 1. MIGRATE DATABASE
Router::add('GET', '/_system/migrate', function () {
    checkSystemKey();

    header('Content-Type: text/plain');
    echo "⚙️ SYSTEM MIGRATION TOOL\n==============================\n";

    try {
        if (!defined('BASE_PATH'))
            define('BASE_PATH', dirname(__DIR__, 2));

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
            define('BASE_PATH', dirname(__DIR__, 2));

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

    if (file_exists($link)) {
        echo "ℹ Symlink already exists.\n";
    } else {
        try {
            symlink($target, $link);
            echo "✅ Symlink created: public/storage -> storage/app/public\n";
        } catch (\Throwable $e) {
            echo "❌ Failed to create symlink: " . $e->getMessage() . "\n";
            echo "   (Note: Shared Hosting might block symlink creation)\n";
        }
    }
});

// 4. ENVIRONMENT INFO (Cek PHP Version & Extension Wajib)
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

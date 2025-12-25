<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;
use Dotenv\Dotenv;

class ConfigCacheCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'config:cache';
    }

    public function getDescription(): string
    {
        return 'Cache konfigurasi .env untuk performa lebih cepat';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;39m➤ INFO  Memproses config caching...\033[0m\n";

        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        try {
            // Kita paksa load .env manual untuk mendapatkan nilai mentahnya
            // Gunakan safeLoad agar tidak error jika file tidak ada (tapi logic cache butuh file)
            $dotenv = Dotenv::createMutable(ROOT_DIR);
            // createMutable agar bisa menimpa env yang mungkin sudah ada di session CLI
            $envVars = $dotenv->load();

            if (empty($envVars)) {
                echo "\033[38;5;214m⚠ WARNING  File .env kosong atau tidak ditemukan.\033[0m\n";
            }

            $cacheContent = "<?php\n\nreturn " . var_export($envVars, true) . ";\n";
            $cacheFile = ROOT_DIR . '/storage/cache/config.php';

            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }

            if (file_put_contents($cacheFile, $cacheContent)) {
                echo "\033[38;5;28m★ SUCCESS  Konfigurasi berhasil di-cache!\033[0m\n";
                echo "\033[38;5;240m  Lokasi: " . $cacheFile . "\033[0m\n";
            } else {
                echo "\033[38;5;124m✖ ERROR  Gagal menulis file cache config.\033[0m\n";
            }

        } catch (\Exception $e) {
            echo "\033[38;5;124m✖ ERROR  " . $e->getMessage() . "\033[0m\n";
        }
    }
}

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
        echo "\n  \033[1;44;97m INFO \033[0m Memproses config caching...\n";

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
                echo "\n  \033[1;43;30m WARN \033[0m File .env kosong atau tidak ditemukan.\n";
            }

            $cacheContent = "<?php\n\nreturn " . var_export($envVars, true) . ";\n";
            $cacheFile = ROOT_DIR . '/storage/cache/config.php';

            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }

            if (file_put_contents($cacheFile, $cacheContent)) {
                echo "\n  \033[1;42;30m SUCCESS \033[0m Konfigurasi berhasil di-cache!\n";
                echo "\033[38;5;240m  Lokasi: " . $cacheFile . "\033[0m\n";
            } else {
                echo "\n  \033[1;41;97m ERROR \033[0m Gagal menulis file cache config.\n";
            }

        } catch (\Exception $e) {
            echo "\n  \033[1;41;97m ERROR \033[0m " . $e->getMessage() . "\n";
        }
    }
}

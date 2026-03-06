<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Dotenv\Dotenv;

class ConfigCacheCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'config:cache';
    }

    public function getDescription(): string
    {
        return 'Cache konfigurasi .env untuk performa lebih cepat';
    }

    public function handle(array $args): void
    {
        $this->info("Memproses config caching...");

        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        try {
            $dotenv = Dotenv::createMutable(ROOT_DIR);
            $envVars = $dotenv->load();

            if (empty($envVars)) {
                $this->warn("File .env kosong atau tidak ditemukan.");
            }

            $cacheContent = "<?php\n\nreturn " . var_export($envVars, true) . ";\n";
            $cacheFile = ROOT_DIR . '/storage/cache/config.php';

            if (!is_dir(dirname($cacheFile))) {
                mkdir(dirname($cacheFile), 0755, true);
            }

            if (file_put_contents($cacheFile, $cacheContent)) {
                $this->success("Konfigurasi berhasil di-cache!");
                $this->line("  Lokasi: " . $cacheFile);
            } else {
                $this->error("Gagal menulis file cache config.");
            }

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}

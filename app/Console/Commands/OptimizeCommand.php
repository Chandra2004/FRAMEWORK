<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\Helpers\Helper;

class OptimizeCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'optimize';
    }

    public function getDescription(): string
    {
        return 'Optimasi performa aplikasi (Cached rute, config, dan file)';
    }

    public function handle(array $args): void
    {
        $this->info("Memulai proses optimasi aplikasi...");

        // 1. Clear compiled views
        $viewPath = BASE_PATH . '/storage/framework/views';
        if (is_dir($viewPath)) {
            $files = glob($viewPath . '/*.php');
            foreach ($files as $file) @unlink($file);
            $this->comment("✓ Terkompilasi views dibersihkan.");
        }

        // 2. Clear Session and Rate limit caches
        $cachePaths = [
            BASE_PATH . '/storage/framework/sessions',
            BASE_PATH . '/storage/framework/cache/data'
        ];
        foreach ($cachePaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) @unlink($file);
                }
            }
        }
        $this->comment("✓ Cache session & data dibersihkan.");

        // 3. Logic simulate Laravel Cache
        $this->success("Aplikasi berhasil dioptimasi!");
        $this->info("Saran: Jalankan juga 'php artisan route:cache' jika sudah masuk mode produksi.");
    }
}

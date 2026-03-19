<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Http\Router;

class RouteCacheCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'route:cache';
    }

    public function getDescription(): string
    {
        return 'Cache file route untuk performa yang lebih cepat';
    }

    public function handle(array $args): void
    {
        $this->info("Sedang memproses route caching...");

        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        // Load semua service providers & routes agar Router terisi
        $this->loadApplication();

        try {
            if (Router::cacheRoutes()) {
                $this->success("Route berhasil di-cache!");
                $this->line("  Lokasi: " . ROOT_DIR . '/storage/cache/routes.php');
            } else {
                $this->error("Gagal menulis file cache.");
            }
        } catch (\Exception $e) {
            $this->error("Gagal melakukan caching: " . $e->getMessage());
        }
    }

    protected function loadApplication(): void
    {
        $routeFile = ROOT_DIR . '/routes/web.php';
        if (file_exists($routeFile)) {
            require_once $routeFile;
        }

        $apiFile = ROOT_DIR . '/routes/api.php';
        if (file_exists($apiFile)) {
            require_once $apiFile;
        }
    }
}

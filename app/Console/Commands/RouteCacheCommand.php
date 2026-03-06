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

        $routeFile = ROOT_DIR . '/routes/web.php';
        if (file_exists($routeFile)) {
            require_once $routeFile;
        }

        $routes = Router::getRouteDefinitions();

        if (empty($routes)) {
            $this->error("Tidak ada route yang ditemukan untuk di-cache.");
            return;
        }

        // Filter routes yang mengandung Closure sebelum cache
        $cacheable = array_filter($routes, function ($route) {
            return !($route['handler'] instanceof \Closure);
        });

        if (count($cacheable) < count($routes)) {
            $this->warn((count($routes) - count($cacheable)) . " route dengan Closure di-skip (tidak bisa di-cache)");
        }

        $cacheContent = "<?php\n\nreturn " . var_export($cacheable, true) . ";\n";
        $cacheFile = ROOT_DIR . '/storage/cache/routes.php';

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }

        if (file_put_contents($cacheFile, $cacheContent)) {
            $this->success("Route berhasil di-cache! (" . count($routes) . " routes)");
            $this->line("  Lokasi: " . $cacheFile);
        } else {
            $this->error("Gagal menulis file cache.");
        }
    }
}

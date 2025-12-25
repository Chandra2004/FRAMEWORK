<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;
use TheFramework\App\Router;

class RouteCacheCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'route:cache';
    }

    public function getDescription(): string
    {
        return 'Cache file route untuk performa yang lebih cepat';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;39m➤ INFO  Sedang memproses route caching...\033[0m\n";

        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        // Fresh load routes
        // PENTING: Kita harus reset Router dulu jika ada sisa route sebelumnya,
        // tapi class Router saat ini property-nya static private tanpa method reset.
        // Asumsi: artisan baru jalan, Router masih kosong.

        $routeFile = ROOT_DIR . '/routes/web.php';
        if (file_exists($routeFile)) {
            require_once $routeFile;
        }

        $routes = Router::getRouteDefinitions();

        if (empty($routes)) {
            echo "\033[38;5;124m✖ ERROR  Tidak ada route yang ditemukan untuk di-cache.\033[0m\n";
            return;
        }

        // Trik Optimasi: Grouping by Method untuk lookup lebih cepat O(1)
        // Struktur cache:
        // [
        //   'GET' => [
        //      '/url' => [...data check direct...],
        //      'REGEX' => [ ...list regex... ]
        //   ]
        // ]
        // Tapi untuk sekarang kita simpan flat dulu agar Router::loadCachedRoutes mudah mencernanya.

        $cacheContent = "<?php\n\nreturn " . var_export($routes, true) . ";\n";
        $cacheFile = ROOT_DIR . '/storage/cache/routes.php';

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }

        if (file_put_contents($cacheFile, $cacheContent)) {
            echo "\033[38;5;28m★ SUCCESS  Route berhasil di-cache! (" . count($routes) . " routes)\033[0m\n";
            echo "\033[38;5;240m  Lokasi: " . $cacheFile . "\033[0m\n";
        } else {
            echo "\033[38;5;124m✖ ERROR  Gagal menulis file cache.\033[0m\n";
        }
    }
}

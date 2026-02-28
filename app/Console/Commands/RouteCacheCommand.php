<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;
use TheFramework\App\Http\Router;

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
        echo "\n  \033[1;44;97m INFO \033[0m Sedang memproses route caching...\n";

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
            echo "\n  \033[1;41;97m ERROR \033[0m Tidak ada route yang ditemukan untuk di-cache.\n";
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
            echo "\n  \033[1;42;30m SUCCESS \033[0m Route berhasil di-cache! (" . count($routes) . " routes)\n";
            echo "\033[38;5;240m  Lokasi: " . $cacheFile . "\033[0m\n";
        } else {
            echo "\n  \033[1;41;97m ERROR \033[0m Gagal menulis file cache.\n";
        }
    }
}

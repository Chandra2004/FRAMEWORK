<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Http\Router;

class RouteListCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'route:list';
    }

    public function getDescription(): string
    {
        return 'Tampilkan daftar rute terklasifikasi secara premium';
    }

    public function handle(array $args): void
    {
        // 🚀 SMART LOAD: Scan and load all relevant route files
        $routeFiles = [
            BASE_PATH . '/routes/web.php',
            BASE_PATH . '/routes/system.php',
            BASE_PATH . '/routes/api.php'
        ];

        foreach ($routeFiles as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }

        $allRoutes = Router::getRoutes();

        if (empty($allRoutes)) {
            $this->warn("Tidak ada rute yang terdaftar.");
            return;
        }

        // --- Categorization Engine ---
        $categories = [
            'APPLICATION' => [],
            'FILE STORAGE' => [],
            'STATIC ASSETS' => [],
            'SEO & SITEMAP' => [],
            'SYSTEM CONTROL' => []
        ];

        foreach ($allRoutes as $route) {
            $path = $route['path'];
            
            if (str_starts_with($path, '/_system')) {
                $categories['SYSTEM CONTROL'][] = $route;
            } elseif (str_starts_with($path, '/file/')) {
                $categories['FILE STORAGE'][] = $route;
            } elseif (str_starts_with($path, '/assets/')) {
                $categories['STATIC ASSETS'][] = $route;
            } elseif ($path === '/sitemap.xml') {
                $categories['SEO & SITEMAP'][] = $route;
            } else {
                $categories['APPLICATION'][] = $route;
            }
        }

        $this->info("----------------------------------------------------------------------------------");
        $this->info(" THE FRAMEWORK ROUTE INVENTORY - CATEGORIZED VIEW");
        $this->info("----------------------------------------------------------------------------------");

        $headers = ['Method', 'URI', 'Action', 'Middleware'];

        foreach ($categories as $catName => $routes) {
            if (empty($routes)) continue;

            $this->info("\n [ " . $catName . " ] (" . count($routes) . " entries)");
            $this->info(str_repeat('-', 82));
            
            $rows = [];
            foreach ($routes as $route) {
                $method = strtoupper($route['method']);
                $color = match ($method) {
                    'GET' => self::COLOR_GREEN,
                    'POST' => self::COLOR_CYAN,
                    'PUT', 'PATCH' => self::COLOR_YELLOW,
                    'DELETE' => self::COLOR_RED,
                    'ANY' => self::COLOR_MAGENTA,
                    default => self::COLOR_RESET
                };

                $handler = $route['handler'];
                $func = $route['function'] ?? 'index';
                $actionStr = 'Unknown';
                
                if (is_string($handler)) {
                    $actionStr = basename(str_replace('\\', '/', $handler)) . '@' . $func;
                } elseif ($handler instanceof \Closure) {
                    $actionStr = 'λ Closure';
                } elseif (is_array($handler)) {
                    $actionStr = basename(str_replace('\\', '/', $handler[0])) . '@' . ($handler[1] ?? 'index');
                }

                $middlewares = [];
                if (!empty($route['middleware'])) {
                    foreach ((array)$route['middleware'] as $m) {
                        $middlewares[] = is_string($m) ? basename(str_replace('\\', '/', $m)) : 'λ';
                    }
                }

                $rows[] = [
                    $color . str_pad($method, 6) . self::COLOR_RESET,
                    $route['path'],
                    $actionStr,
                    !empty($middlewares) ? implode(',', $middlewares) : '-'
                ];
            }

            $this->table($headers, $rows);
        }

        $this->info("\n----------------------------------------------------------------------------------");
        $this->info(" SUMMARY:");
        foreach ($categories as $cat => $r) {
            if (count($r) > 0) {
                $this->info(" - " . str_pad($cat, 16) . ": " . count($r) . " routes");
            }
        }
        $this->info("----------------------------------------------------------------------------------");
    }
}

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
        return 'Tampilkan daftar rute dalam tabel premium';
    }

    public function handle(array $args): void
    {
        // Load routes from web.php
        require_once BASE_PATH . '/routes/web.php';

        $routes = Router::getRoutes();

        if (empty($routes)) {
            $this->warn("Tidak ada rute yang terdaftar.");
            return;
        }

        $headers = ['Method', 'URI', 'Name', 'Action', 'Middleware'];
        $rows = [];

        foreach ($routes as $route) {
            $method = $route['method'];
            $color = match ($method) {
                'GET' => self::COLOR_GREEN,
                'POST' => self::COLOR_CYAN,
                'PUT', 'PATCH' => self::COLOR_YELLOW,
                'DELETE' => self::COLOR_RED,
                default => self::COLOR_RESET
            };

            // Format Action
            $handler = $route['handler'];
            if (is_string($handler)) {
                $function = $route['function'] ?? '__invoke';
                $actionStr = basename(str_replace('\\', '/', $handler)) . '@' . $function;
            } elseif ($handler instanceof \Closure) {
                $actionStr = 'Closure';
            } elseif (is_array($handler)) {
                $actionStr = basename(str_replace('\\', '/', $handler[0])) . '@' . $handler[1];
            } else {
                $actionStr = 'Unknown';
            }

            // Shorten middlewares
            $middlewares = [];
            if (!empty($route['middleware'])) {
                foreach ((array) $route['middleware'] as $m) {
                    $middlewares[] = is_string($m) ? basename(str_replace('\\', '/', $m)) : 'Closure';
                }
            }

            $rows[] = [
                $color . $method . self::COLOR_RESET,
                $route['path'],
                $route['name'] ?? '-',
                $actionStr,
                !empty($middlewares) ? implode(', ', $middlewares) : '-'
            ];
        }

        $this->info("Menampilkan " . count($routes) . " rute terdaftar:");
        $this->table($headers, $rows);
    }
}

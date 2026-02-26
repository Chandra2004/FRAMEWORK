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
            $color = match($method) {
                'GET' => self::COLOR_GREEN,
                'POST' => self::COLOR_CYAN,
                'PUT', 'PATCH' => self::COLOR_YELLOW,
                'DELETE' => self::COLOR_RED,
                default => self::COLOR_RESET
            };

            // Format Action
            $action = $route['action'];
            if (is_array($action)) {
                $actionStr = basename($action[0]) . '@' . $action[1];
            } elseif (is_object($action)) {
                $actionStr = 'Closure';
            } else {
                $actionStr = (string)$action;
            }

            $rows[] = [
                $color . $method . self::COLOR_RESET,
                $route['uri'],
                $route['name'] ?? '-',
                $actionStr,
                !empty($route['middleware']) ? implode(', ', (array)$route['middleware']) : '-'
            ];
        }

        $this->info("Menampilkan " . count($routes) . " rute terdaftar:");
        $this->table($headers, $rows);
    }
}

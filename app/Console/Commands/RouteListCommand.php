<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;
use TheFramework\App\Router;
use ReflectionClass;

class RouteListCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'route:list';
    }

    public function getDescription(): string
    {
        return 'Menampilkan daftar semua rute yang terdaftar dalam aplikasi';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;39m➤ INFO  Memuat daftar rute...\033[0m\n";

        try {
            // Akses property protected 'routes' via Reflection
            $ref = new ReflectionClass('TheFramework\App\Router');
            $prop = $ref->getProperty('routes');
            $prop->setAccessible(true);
            $routes = $prop->getValue();
        } catch (\Throwable $e) {
            echo "\033[31m✖ ERROR  Gagal membaca rute: " . $e->getMessage() . "\033[0m\n";
            return;
        }

        // Tampilkan Header Tabel
        echo str_repeat("─", 80) . "\n";
        printf("\033[1;33m%-10s %-40s %s\033[0m\n", "METHOD", "URI", "ACTION");
        echo str_repeat("─", 80) . "\n";

        // Tampilkan Isi
        foreach ($routes as $route) {
            // Struktur route di framework custom ini sepertinya array linear
            // dengan key: 'method', 'path', 'handler', 'function', dll

            $method = $route['method'] ?? 'UNK';
            $uri = $route['path'] ?? '/';

            // Bersihkan RegEx dari URI (contoh: #^/users$#i -> /users)
            $uri = preg_replace('/#\^|\$#i/', '', $uri);
            $uri = str_replace('\/', '/', $uri);

            // Ubah Named Group (?P<id>...) menjadi {id}
            $uri = preg_replace('/\(\?P<(\w+)>[^)]+\)/', '{$1}', $uri);

            // Handler logic
            $handler = $route['handler'] ?? '';
            $function = $route['function'] ?? '';

            if (is_string($handler) && is_string($function)) {
                $actionName = $handler . '@' . $function;
                // Bersihkan namespace full class
                $actionName = str_replace('TheFramework\\Http\\Controllers\\', '', $actionName);
            } elseif ($handler instanceof \Closure) {
                $actionName = 'Closure()';
            } else {
                $actionName = 'Unknown';
            }

            // Warna method
            $methodColor = match ($method) {
                'GET' => "\033[32mGET\033[0m",
                'POST' => "\033[33mPOST\033[0m",
                'PUT' => "\033[34mPUT\033[0m",
                'DELETE' => "\033[31mDEL\033[0m",
                default => $method
            };

            // Print row
            echo str_pad($methodColor, 20) . str_pad($uri, 40) . substr($actionName, 0, 40) . "\n";
        }
        echo str_repeat("─", 80) . "\n";
    }
}

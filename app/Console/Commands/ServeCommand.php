<?php

namespace TheFramework\Console\Commands;

use TheFramework\App\Config;
use TheFramework\Console\CommandInterface;

class ServeCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'serve';
    }
    public function getDescription(): string
    {
        return 'Menjalankan aplikasi pada server pengembangan PHP';
    }

    public function run(array $args): void
    {
        Config::loadEnv();
        $env = strtoupper(Config::get('APP_ENV', 'LOCAL'));

        // Parse BASE_URL to extract host and port dynamically
        $defaultHost = '127.0.0.1';
        $defaultPort = '8080';
        $displayUrl = '';

        $baseUrl = Config::get('BASE_URL', '');
        if (!empty($baseUrl)) {
            $parsed = parse_url($baseUrl);

            // Extract host (domain/hostname)
            if (isset($parsed['host'])) {
                $displayUrl = $parsed['host'];
            }

            // Extract port or use scheme default
            if (isset($parsed['port'])) {
                $defaultPort = (string) $parsed['port'];
            } elseif (isset($parsed['scheme'])) {
                // Use default ports based on scheme
                $defaultPort = ($parsed['scheme'] === 'https') ? '443' : '80';
            }
        }

        // Get user inputs (can override BASE_URL settings)
        $host = $args[0] ?? $defaultHost;
        $port = $args[1] ?? $defaultPort;

        // ✅ SECURITY FIX: Validate IP address to prevent command injection
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            echo "\033[31m✖ ERROR  Invalid IP address: {$host}\033[0m\n";
            echo "\033[33mUsage: php artisan serve [host] [port]\033[0m\n";
            echo "\033[33mExample: php artisan serve 127.0.0.1 8080\033[0m\n";
            exit(1);
        }

        // ✅ SECURITY FIX: Validate port number (1-65535)
        $port = filter_var($port, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535]
        ]);
        if ($port === false) {
            echo "\033[31m✖ ERROR  Invalid port number. Must be between 1-65535\033[0m\n";
            echo "\033[33mUsage: php artisan serve [host] [port]\033[0m\n";
            exit(1);
        }

        // Display dynamic information based on BASE_URL
        echo "\033[38;5;39m➤ INFO  TheFramework $env Server\033[0m\n";

        if (!empty($displayUrl)) {
            // Show BASE_URL website/domain if available
            echo "\033[38;5;39m  Website: {$displayUrl}\033[0m\n";
        }

        echo "\033[38;5;39m  Server berjalan di http://$host:$port\033[0m\n";
        echo "\033[38;5;39m  Port: {$port}\033[0m\n";
        echo "\033[38;5;39m  Tekan Ctrl+C untuk menghentikan\033[0m\n\n";

        // ✅ SECURITY FIX: Use escapeshellarg() to prevent command injection
        $cmd = sprintf(
            'php -S %s:%s index.php',
            escapeshellarg($host),
            escapeshellarg((string) $port)
        );

        passthru($cmd);
    }
}

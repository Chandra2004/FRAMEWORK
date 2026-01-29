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

        // Get user inputs
        $host = $args[0] ?? '127.0.0.1';
        $port = $args[1] ?? '8080';

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

        echo "\033[38;5;39m➤ INFO  TheFramework $env Server\033[0m\n";
        echo "\033[38;5;39m  Server berjalan di http://$host:$port\033[0m\n";
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

<?php

namespace TheFramework\Console\Commands;

use TheFramework\App\Core\Config;
use TheFramework\Console\BaseCommand;

class ServeCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Jalankan server pengembangan dengan deteksi IP & Auto-Port';
    }

    public function handle(array $args): void
    {
        Config::loadEnv();
        $env = strtoupper(Config::get('APP_ENV', 'LOCAL'));

        // Auto-detect host & port from BASE_URL in .env
        $baseUrl = Config::get('BASE_URL', 'http://127.0.0.1:8080');
        $parsedUrl = parse_url($baseUrl);
        $envHost = $parsedUrl['host'] ?? '127.0.0.1';
        $envPort = $parsedUrl['port'] ?? 8080;

        // Manual args override .env values
        $host = $args[0] ?? $envHost;
        $port = $args[1] ?? $envPort;

        // Resolve hostname ke IP (PHP built-in server butuh IP, bukan hostname)
        $hostnameMap = [
            'localhost' => '127.0.0.1',
            '0.0.0.0'  => '0.0.0.0',
        ];
        $displayHost = $host; // Simpan hostname asli untuk display
        $host = $hostnameMap[strtolower($host)] ?? $host;

        // Jika masih bukan IP valid, coba resolve via DNS
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            $resolved = gethostbyname($host);
            if ($resolved !== $host) {
                $displayHost = $host;
                $host = $resolved;
            } else {
                $this->error("Host tidak dapat di-resolve: $host");
                exit(1);
            }
        }

        $port = filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($port === false) {
            $this->error("Port harus angka (1-65535)");
            exit(1);
        }

        $this->clear();
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);
        $this->line("  THE FRAMEWORK v5.0 DEVELOPMENT SERVER", self::STYLE_BOLD . self::COLOR_CYAN);
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);
        
        $this->info("Environment : " . self::STYLE_BOLD . $env);
        $this->info("Server Host : " . self::STYLE_BOLD . "http://$host:$port");
        $this->info("Base URL    : " . self::STYLE_BOLD . $baseUrl);
        $this->warn("Tekan Ctrl+C untuk menghentikan server.");
        echo PHP_EOL;

        $cmd = sprintf(
            'php -S %s:%s index.php',
            escapeshellarg($host),
            escapeshellarg((string)$port)
        );

        passthru($cmd);
    }
}

<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Core\Config;

class EnvCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'env';
    }

    public function getDescription(): string
    {
        return 'Menampilkan environment aplikasi saat ini';
    }

    public function handle(array $args): void
    {
        Config::loadEnv();

        $env = Config::environment();
        $debug = Config::isDebug();
        $url = Config::get('BASE_URL', 'http://localhost:8080');
        $name = Config::get('APP_NAME', 'The Framework');
        $timezone = Config::get('APP_TIMEZONE', 'Asia/Jakarta');
        $locale = Config::get('APP_LOCALE', 'id');
        $maintenance = Config::getBool('APP_MAINTENANCE', false);
        $dbHost = Config::get('DB_HOST', '—');
        $dbName = Config::get('DB_NAME', '—');
        $cacheDriver = Config::get('CACHE_DRIVER', 'file');
        $sessionDriver = Config::get('SESSION_DRIVER', 'file');
        $logChannel = Config::get('LOG_CHANNEL', 'daily');

        echo PHP_EOL;
        $this->line("  ╔══════════════════════════════════════════╗", self::COLOR_CYAN);
        $this->line("  ║       ⚙️  ENVIRONMENT INFORMATION         ║", self::COLOR_CYAN);
        $this->line("  ╚══════════════════════════════════════════╝", self::COLOR_CYAN);
        echo PHP_EOL;

        $envColor = match($env) {
            'production', 'prod' => self::COLOR_RED,
            'staging' => self::COLOR_YELLOW,
            'testing', 'test' => self::COLOR_MAGENTA,
            'maintenance' => self::COLOR_YELLOW,
            default => self::COLOR_GREEN,
        };

        $this->line("  Application  : " . self::STYLE_BOLD . $name, self::COLOR_GRAY);
        $this->line("  Environment  : " . $envColor . self::STYLE_BOLD . strtoupper($env) . self::COLOR_RESET);
        $this->line("  Debug Mode   : " . ($debug ? self::COLOR_YELLOW . "ON" : self::COLOR_GREEN . "OFF") . self::COLOR_RESET);
        $this->line("  Maintenance  : " . ($maintenance ? self::COLOR_RED . "ACTIVE" : self::COLOR_GREEN . "OFF") . self::COLOR_RESET);
        $this->line("  URL          : " . self::STYLE_BOLD . $url, self::COLOR_GRAY);
        $this->line("  Timezone     : " . $timezone, self::COLOR_GRAY);
        $this->line("  Locale       : " . $locale, self::COLOR_GRAY);
        echo PHP_EOL;
        $this->line("  Database     : " . $dbHost . " / " . $dbName, self::COLOR_GRAY);
        $this->line("  Cache        : " . $cacheDriver, self::COLOR_GRAY);
        $this->line("  Session      : " . $sessionDriver, self::COLOR_GRAY);
        $this->line("  Log Channel  : " . $logChannel, self::COLOR_GRAY);
        echo PHP_EOL;
    }
}

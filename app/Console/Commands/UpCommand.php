<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Core\Config;

class UpCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'up';
    }

    public function getDescription(): string
    {
        return 'Nonaktifkan maintenance mode (aplikasi kembali online)';
    }

    public function handle(array $args): void
    {
        Config::loadEnv();
        $envFile = BASE_PATH . '/.env';

        if (!file_exists($envFile)) {
            $this->error("File .env tidak ditemukan.");
            return;
        }

        $content = file_get_contents($envFile);

        // Set APP_MAINTENANCE=false
        if (preg_match('/^APP_MAINTENANCE=.*/m', $content)) {
            $content = preg_replace('/^APP_MAINTENANCE=.*/m', 'APP_MAINTENANCE=false', $content);
        }

        // Also reset APP_ENV if it was set to maintenance
        if (preg_match('/^APP_ENV=maintenance/m', $content)) {
            $content = preg_replace('/^APP_ENV=maintenance/m', 'APP_ENV=local', $content);
            $this->info("APP_ENV di-reset dari 'maintenance' ke 'local'.");
        }

        // Clear maintenance secret
        if (preg_match('/^MAINTENANCE_SECRET=.*/m', $content)) {
            $content = preg_replace('/^MAINTENANCE_SECRET=.*/m', 'MAINTENANCE_SECRET=', $content);
        }

        file_put_contents($envFile, $content);

        echo PHP_EOL;
        $this->line("  ╔══════════════════════════════════════════╗", self::COLOR_GREEN);
        $this->line("  ║            APPLICATION IS NOW            ║", self::COLOR_GREEN);
        $this->line("  ║           ✅ LIVE & ONLINE ✅            ║", self::COLOR_GREEN);
        $this->line("  ╚══════════════════════════════════════════╝", self::COLOR_GREEN);
        echo PHP_EOL;

        $this->success("Maintenance mode dinonaktifkan. Aplikasi kembali online!");
    }
}

<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Core\Config;

class DownCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'down';
    }

    public function getDescription(): string
    {
        return 'Aktifkan maintenance mode (menampilkan halaman 503)';
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

        // Check --secret flag
        $secret = null;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--secret=')) {
                $secret = substr($arg, 9);
            }
        }

        // Update APP_MAINTENANCE=true
        if (preg_match('/^APP_MAINTENANCE=.*/m', $content)) {
            $content = preg_replace('/^APP_MAINTENANCE=.*/m', 'APP_MAINTENANCE=true', $content);
        } else {
            $content .= "\nAPP_MAINTENANCE=true\n";
        }

        // Update MAINTENANCE_SECRET if provided
        if ($secret !== null) {
            if (preg_match('/^MAINTENANCE_SECRET=.*/m', $content)) {
                $content = preg_replace('/^MAINTENANCE_SECRET=.*/m', "MAINTENANCE_SECRET={$secret}", $content);
            } else {
                $content .= "\nMAINTENANCE_SECRET={$secret}\n";
            }
        }

        file_put_contents($envFile, $content);

        echo PHP_EOL;
        $this->line("  ╔══════════════════════════════════════════╗", self::COLOR_YELLOW);
        $this->line("  ║          APPLICATION IS NOW IN           ║", self::COLOR_YELLOW);
        $this->line("  ║         🚧 MAINTENANCE MODE 🚧          ║", self::COLOR_YELLOW);
        $this->line("  ╚══════════════════════════════════════════╝", self::COLOR_YELLOW);
        echo PHP_EOL;

        $this->success("Maintenance mode diaktifkan.");

        if ($secret) {
            $this->info("Secret URL: " . self::STYLE_BOLD . Config::get('BASE_URL', 'http://localhost:8080') . "?secret={$secret}");
        }

        $this->comment("Jalankan 'php artisan up' untuk menonaktifkan.");
    }
}

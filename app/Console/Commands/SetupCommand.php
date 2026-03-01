<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class SetupCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'setup';
    }

    public function getDescription(): string
    {
        return 'Inisialisasi awal aplikasi (ENV, APP_KEY, & Security Setup)';
    }

    public function handle(array $args): void
    {
        $this->clear();
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);
        $this->line("  THE FRAMEWORK v5.0 SETUP WIZARD", self::STYLE_BOLD . self::COLOR_CYAN);
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);

        // 1. .env Setup
        if (!file_exists('.env')) {
            if (file_exists('.env.example')) {
                copy('.env.example', '.env');
                $this->success("File .env berhasil dibuat dari template.");
            } else {
                $this->error("File .env.example tidak ditemukan!");
                exit(1);
            }
        }

        $env = file_get_contents('.env');

        // 2. Generate APP_KEY
        if (!preg_match('/^APP_KEY=base64:.+/m', $env)) {
            $key = 'base64:' . base64_encode(random_bytes(32));
            if (preg_match('/^APP_KEY=/m', $env)) {
                $env = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $env);
            } else {
                $env .= "\nAPP_KEY={$key}";
            }
            file_put_contents('.env', $env);
            $this->success("APP_KEY baru di-generate: $key");
        } else {
            $this->comment("APP_KEY sudah ada, melewati proses...");
        }

        // 3. Web Command Center Security
        $this->info("Menyiapkan Keamanan Web Command Center (/_system/*)");
        if ($this->confirm("Aktifkan Basic Auth sekarang?", true)) {
            $username = $this->ask("Username Admin", "admin");
            $password = $this->ask("Password Admin (min 8 char)");

            if (strlen($password) < 8) {
                $this->warn("Password terlalu pendek! Setup dibatalkan.");
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $safeHash = str_replace('$', '\\$', $hashed);

                $env = preg_replace('/^SYSTEM_AUTH_USER=.*/m', "SYSTEM_AUTH_USER={$username}", $env);
                if (!preg_match('/^SYSTEM_AUTH_USER=/m', $env))
                    $env .= "\nSYSTEM_AUTH_USER={$username}";

                $env = preg_replace('/^SYSTEM_AUTH_PASS=.*/m', "SYSTEM_AUTH_PASS={$safeHash}", $env);
                if (!preg_match('/^SYSTEM_AUTH_PASS=/m', $env))
                    $env .= "\nSYSTEM_AUTH_PASS={$hashed}";

                file_put_contents('.env', $env);
                $this->success("Security configured: User [$username] Hashed Password Set.");
            }
        }

        // 4. Database Configuration
        $this->line("\n──────────────────────────────────────────────────", self::COLOR_MAGENTA);
        $this->line("  DATABASE CONFIGURATION", self::STYLE_BOLD . self::COLOR_CYAN);
        $this->line("──────────────────────────────────────────────────", self::COLOR_MAGENTA);

        if ($this->confirm("Konfigurasi database sekarang?", true)) {
            $dbHost = $this->ask("DB Host", "127.0.0.1");
            $dbName = $this->ask("DB Name", "the_framework");
            $dbUser = $this->ask("DB User", "root");
            $dbPass = $this->ask("DB Password", "");

            $env = preg_replace('/^DB_HOST=.*/m', "DB_HOST={$dbHost}", $env);
            $env = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$dbName}", $env);
            $env = preg_replace('/^DB_USERNAME=.*/m', "DB_USERNAME={$dbUser}", $env);
            $env = preg_replace('/^DB_PASSWORD=.*/m', "DB_PASSWORD={$dbPass}", $env);

            file_put_contents('.env', $env);
            $this->success("Database configuration saved to .env");
        }

        $this->info("Memperbarui composer autoload...");
        passthru('composer dump-autoload');

        $this->success("Penyetelan Framework selesai! Selamat berkarya.");
    }
}

<?php

namespace TheFramework\Console\Commands;


use TheFramework\Console\CommandInterface;

class SetupCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'setup';
    }
    public function getDescription(): string
    {
        return 'Menjalankan pengaturan awal (env, kunci, autoload)';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;39m➤ INFO  Memulai pengaturan TheFramework";
        for ($i = 0; $i < 3; $i++) {
            echo ".";
            usleep(200000);
        }
        echo "\033[0m\n";

        if (!file_exists('.env')) {
            if (file_exists('.env.example')) {
                copy('.env.example', '.env');
                echo "\033[38;5;28m★ SUCCESS  File .env dibuat dari .env.example\033[0m\n";
            } else {
                echo "\033[38;5;124m✖ ERROR  File .env.example tidak ditemukan\033[0m\n";
                exit(1);
            }
        }

        $env = file_get_contents('.env');

        // ENCRYPTION_KEY block removed (unused dependency)

        // Cek apakah APP_KEY sudah terisi
        if (preg_match('/^APP_KEY=base64:.+/m', $env)) {
            // Sudah ada key valid, skip
        } else {
            // Generate Key Baru
            $key = 'base64:' . base64_encode(random_bytes(32));

            if (preg_match('/^APP_KEY=/m', $env)) {
                // Replace existing empty key
                $env = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $env);
            } else {
                // Append new key
                $env .= "\nAPP_KEY={$key}";
            }

            file_put_contents('.env', $env);
            echo "\033[38;5;28m★ SUCCESS  APP_KEY berhasil di-generate [{$key}]\033[0m\n";
        }

        // === v5.0.0: Web Command Center Security Setup ===
        echo "\n\033[38;5;39m🔐 Web Command Center Security Setup (v5.0.0)\033[0m\n";
        echo "\033[38;5;244mSetup Basic Authentication untuk melindungi /_system/* routes\033[0m\n";
        echo "\033[38;5;244m(Tekan Enter untuk skip jika tidak ingin mengaktifkan)\033[0m\n\n";

        // Username input
        echo "\033[38;5;33mUsername untuk system admin\033[0m [default: admin]: ";
        $username = trim(fgets(STDIN));
        if (empty($username)) {
            $username = 'admin';
        }

        // Password input dengan hidden characters
        echo "\033[38;5;33mPassword untuk system admin\033[0m (min 8 char, strong recommended): ";

        // Hide password input (works on Unix/Mac/Windows with some terminals)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Best effort, might show characters
            $password = trim(fgets(STDIN));
        } else {
            // Unix/Mac: Hide input
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');
            echo "\n";
        }

        if (!empty($password)) {
            // Validate password strength
            if (strlen($password) < 8) {
                echo "\033[38;5;124m⚠ WARNING  Password terlalu pendek! Minimal 8 karakter.\033[0m\n";
                echo "\033[38;5;244mMelanjutkan tanpa setup Basic Auth...\033[0m\n";
            } else {
                // Hash password menggunakan bcrypt
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Update .env dengan username dan hashed password
                // Update .env dengan username dan hashed password
                // Note: Kita gunakan str_replace untuk escape $ agar tidak dianggap backreference oleh preg_replace
                $safeHash = str_replace('$', '\\$', $hashedPassword);

                if (preg_match('/^SYSTEM_AUTH_USER=/m', $env)) {
                    $env = preg_replace('/^SYSTEM_AUTH_USER=.*/m', "SYSTEM_AUTH_USER={$username}", $env);
                } else {
                    $env .= "\nSYSTEM_AUTH_USER={$username}";
                }

                if (preg_match('/^SYSTEM_AUTH_PASS=/m', $env)) {
                    $env = preg_replace('/^SYSTEM_AUTH_PASS=.*/m', "SYSTEM_AUTH_PASS={$safeHash}", $env);
                } else {
                    $env .= "\nSYSTEM_AUTH_PASS={$hashedPassword}";
                }

                file_put_contents('.env', $env);
                echo "\033[38;5;28m★ SUCCESS  Basic Auth configured:\033[0m\n";
                echo "  Username: \033[38;5;33m{$username}\033[0m\n";
                echo "  Password: \033[38;5;244m[hashed with bcrypt]\033[0m\n";
                echo "\033[38;5;244m  Hash: {$hashedPassword}\033[0m\n";
            }
        } else {
            echo "\033[38;5;244m⊘ SKIPPED  Basic Auth tidak diaktifkan (bisa disetup manual di .env)\033[0m\n";
        }

        echo "\n\033[38;5;39m➤ INFO  Menjalankan composer dump-autoload...\033[0m\n";
        passthru('composer dump-autoload');
        echo "\n\033[38;5;28m★ SUCCESS  Autoload Composer diperbarui\033[0m\n";
        echo "\033[38;5;28m★ SUCCESS  Pengaturan selesai! Jalankan 'php artisan serve' untuk memulai\033[0m\n";
    }
}

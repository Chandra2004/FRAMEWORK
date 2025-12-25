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

        if (!preg_match('/APP_KEY=(.+)/', $env)) {
            $appKey = base64_encode(random_bytes(32));
            $env .= "\nAPP_KEY=base64:$appKey";
            file_put_contents('.env', $env);
            echo "\033[38;5;28m★ SUCCESS  APP_KEY dibuat\033[0m\n";
        }

        shell_exec('composer dump-autoload');
        echo "\033[38;5;28m★ SUCCESS  Autoload Composer diperbarui\033[0m\n";
        echo "\033[38;5;28m★ SUCCESS  Pengaturan selesai! Jalankan 'php artisan serve' untuk memulai\033[0m\n";
    }
}

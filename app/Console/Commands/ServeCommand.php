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

        $host = $args[0] ?? '127.0.0.1';
        $port = $args[1] ?? '8080';
        echo "\033[38;5;39m➤ INFO  TheFramework $env Server\033[0m\n";
        echo "\033[38;5;39m  Server berjalan di http://$host:$port\033[0m\n";
        echo "\033[38;5;39m  Tekan Ctrl+C untuk menghentikan\033[0m\n\n";
        passthru("php -S $host:$port index.php");
    }
}

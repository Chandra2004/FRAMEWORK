<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class OptimizeCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'optimize';
    }

    public function getDescription(): string
    {
        return 'Menghapus cache konfigurasi, route, dan view untuk optimasi performa';
    }

    public function run(array $args): void
    {
        echo "\033[38;5;39m➤ INFO  Mengoptimalkan aplikasi...\033[0m\n";

        $dirs = [
            BASE_PATH . '/storage/framework/views',
            BASE_PATH . '/storage/framework/cache',
            BASE_PATH . '/storage/cache/ratelimit',
            BASE_PATH . '/storage/cache',
            BASE_PATH . '/storage/logs'
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir))
                continue;

            $files = glob($dir . '/*');
            $count = 0;

            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    unlink($file);
                    $count++;
                }
            }
            if ($count > 0) {
                echo "\033[32m✔ Cleared:\033[0m " . str_replace(BASE_PATH, '', $dir) . " ({$count} files)\n";
            }
        }

        // Cache routes (panggil RouteCacheCommand logic atau exec)
        // Di sini kita simulasi clear opcache juga
        if (function_exists('opcache_reset')) {
            opcache_reset();
            echo "\033[32m✔ OpCache:\033[0m Reset successful\n";
        }

        echo "\n\033[38;5;28m★ SUCCESS  Aplikasi berhasil dioptimalkan!\033[0m\n";
    }
}

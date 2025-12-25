<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class RouteClearCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'route:clear';
    }

    public function getDescription(): string
    {
        return 'Hapus file cache route';
    }

    public function run(array $args): void
    {
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        $cacheFile = ROOT_DIR . '/storage/cache/routes.php';

        if (file_exists($cacheFile)) {
            if (unlink($cacheFile)) {
                echo "\033[38;5;28m★ SUCCESS  Route cache berhasil dihapus.\033[0m\n";
            } else {
                echo "\033[38;5;124m✖ ERROR  Gagal menghapus file cache.\033[0m\n";
            }
        } else {
            echo "\033[38;5;214m➤ INFO  Tidak ada file cache route yang ditemukan.\033[0m\n";
        }
    }
}

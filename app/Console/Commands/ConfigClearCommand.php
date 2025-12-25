<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;

class ConfigClearCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'config:clear';
    }

    public function getDescription(): string
    {
        return 'Hapus file cache konfigurasi';
    }

    public function run(array $args): void
    {
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__, 3));
        }

        $cacheFile = ROOT_DIR . '/storage/cache/config.php';

        if (file_exists($cacheFile)) {
            if (unlink($cacheFile)) {
                echo "\033[38;5;28m★ SUCCESS  Config cache berhasil dihapus.\033[0m\n";
            } else {
                echo "\033[38;5;124m✖ ERROR  Gagal menghapus file cache config.\033[0m\n";
            }
        } else {
            echo "\033[38;5;214m➤ INFO  Tidak ada file cache config yang ditemukan.\033[0m\n";
        }
    }
}

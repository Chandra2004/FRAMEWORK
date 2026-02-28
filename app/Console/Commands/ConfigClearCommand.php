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
                echo "\n  \033[1;42;30m SUCCESS \033[0m Config cache berhasil dihapus.\n";
            } else {
                echo "\n  \033[1;41;97m ERROR \033[0m Gagal menghapus file cache config.\n";
            }
        } else {
            echo "\n  \033[1;44;97m INFO \033[0m Tidak ada file cache config yang ditemukan.\n";
        }
    }
}

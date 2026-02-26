<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class CacheClearCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'cache:clear';
    }

    public function getDescription(): string
    {
        return 'Hapus semua file cache aplikasi';
    }

    public function handle(array $args): void
    {
        $cacheDir = BASE_PATH . '/storage/framework/cache';
        $count = 0;

        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    unlink($file);
                    $count++;
                }
            }
        }

        $this->success("Cache berhasil dihapus. ($count file)");
    }
}

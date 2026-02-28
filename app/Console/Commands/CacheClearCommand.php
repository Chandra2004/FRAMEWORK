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
        $cacheDirs = [
            BASE_PATH . '/storage/framework/cache',
            BASE_PATH . '/storage/cache'
        ];

        $count = 0;

        foreach ($cacheDirs as $cacheDir) {
            if (is_dir($cacheDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($iterator as $file) {
                    $filename = $file->getFilename();
                    if ($filename === '.gitignore')
                        continue;

                    if ($file->isDir()) {
                        @rmdir($file->getRealPath());
                    } else {
                        @unlink($file->getRealPath());
                        $count++;
                    }
                }
            }
        }

        $this->success("Cache berhasil dihapus. ($count file dari semua direktori)");
    }
}

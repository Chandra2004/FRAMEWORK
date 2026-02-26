<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class ViewClearCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'view:clear';
    }

    public function getDescription(): string
    {
        return 'Hapus semua compiled Blade views dari cache';
    }

    public function handle(array $args): void
    {
        $viewCacheDir = BASE_PATH . '/storage/framework/views';
        $count = 0;

        if (is_dir($viewCacheDir)) {
            $files = glob($viewCacheDir . '/*.php');
            foreach ($files as $file) {
                unlink($file);
                $count++;
            }
        }

        $this->success("Compiled views berhasil dihapus. ($count file)");
    }
}

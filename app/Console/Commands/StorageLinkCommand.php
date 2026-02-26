<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class StorageLinkCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'storage:link';
    }

    public function getDescription(): string
    {
        return 'Buat symbolic link dari public/storage ke storage/app/public';
    }

    public function handle(array $args): void
    {
        $target = BASE_PATH . '/storage/app/public';
        $link = BASE_PATH . '/public/storage';

        // Ensure target directory exists
        if (!is_dir($target)) {
            mkdir($target, 0777, true);
            $this->comment("Folder storage/app/public dibuat.");
        }

        // Ensure public directory exists
        if (!is_dir(BASE_PATH . '/public')) {
            mkdir(BASE_PATH . '/public', 0777, true);
        }

        // Check if link already exists
        if (file_exists($link) || is_link($link)) {
            $this->warn("Link sudah ada: public/storage");
            
            if ($this->confirm("Hapus dan buat ulang?", false)) {
                if (is_link($link)) {
                    if (PHP_OS_FAMILY === 'Windows') {
                        rmdir($link);
                    } else {
                        unlink($link);
                    }
                } else {
                    $this->error("public/storage bukan symbolic link. Hapus manual terlebih dahulu.");
                    return;
                }
            } else {
                return;
            }
        }

        // Create symlink
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use mklink /J (junction, doesn't require admin)
            $cmd = sprintf('mklink /J "%s" "%s"', str_replace('/', '\\', $link), str_replace('/', '\\', $target));
            exec($cmd, $output, $code);

            if ($code !== 0) {
                $this->error("Gagal membuat link. Coba jalankan terminal sebagai Administrator.");
                $this->comment("Atau salin folder manual: storage/app/public → public/storage");
                return;
            }
        } else {
            symlink($target, $link);
        }

        $this->success("Link berhasil dibuat:");
        $this->line("  public/storage → storage/app/public", self::COLOR_CYAN);
    }
}

<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class OptimizeClearCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'optimize:clear';
    }

    public function getDescription(): string
    {
        return 'Hapus SEMUA cache, views terkompilasi, log, dan session (Hard Reset Storage)';
    }

    public function handle(array $args): void
    {
        $this->info("Membersihkan semua file temporary, cache, log, dan session...");

        // Jalankan perintah clear standar
        $commands = [
            'view:clear' => 'Compiled views',
            'cache:clear' => 'Application cache',
            'route:clear' => 'Route cache',
            'config:clear' => 'Configuration cache'
        ];

        foreach ($commands as $cmd => $desc) {
            // Kita bisa mengeksekusi langsung logic standar
            $this->comment("✓ $desc dibersihkan.");
        }

        // Panggil command clear secara eksplisit (cara cepat tanpa kernel overhead)
        (new ViewClearCommand())->run([]);
        (new CacheClearCommand())->run([]);
        (new RouteClearCommand())->run([]);
        (new ConfigClearCommand())->run([]);

        // 1. Bersihkan Log (storage/logs)
        $this->deleteFiles(BASE_PATH . '/storage/logs', '.log');
        $this->comment("✓ File Log berhasil dibersihkan.");

        // 2. Bersihkan Session (storage/session & storage/framework/sessions)
        $this->deleteFiles(BASE_PATH . '/storage/session');
        $this->deleteFiles(BASE_PATH . '/storage/framework/sessions');
        $this->comment("✓ File Session (users logged out) dibersihkan.");

        // 3. Bersihkan Full Cache Directory termasuk Ratelimit (storage/cache & storage/framework/cache)
        $this->deleteFiles(BASE_PATH . '/storage/cache/ratelimit');
        $this->deleteFiles(BASE_PATH . '/storage/cache');
        $this->comment("✓ Direktori Cache dan Ratelimit dibersihkan secara rekursif.");

        $this->success("Optimasi selesai: Semua cache, session, ratelimit, dan logs bersih tanpa sisa! ✨");
    }

    /**
     * Helper mutlak untuk menghapus isi folder secara rekursif
     */
    private function deleteFiles(string $dir, string $extension = ''): void
    {
        if (!is_dir($dir))
            return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $filename = $file->getFilename();
            // Lewati file .gitignore
            if ($filename === '.gitignore')
                continue;

            if ($file->isDir()) {
                // Biarkan foldernya saja jika mau aman, atau rmdir jika kosong
                @rmdir($file->getRealPath());
            } else {
                if ($extension !== '' && !str_ends_with($filename, $extension)) {
                    continue;
                }
                @unlink($file->getRealPath());
            }
        }
    }
}

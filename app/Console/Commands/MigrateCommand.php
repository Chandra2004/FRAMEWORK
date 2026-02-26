<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Throwable;

class MigrateCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'migrate';
    }

    public function getDescription(): string
    {
        return 'Jalankan database migration dengan status tracking';
    }

    public function handle(array $args): void
    {
        $migrationDir = BASE_PATH . '/database/migrations/';
        $migrationFiles = glob($migrationDir . '*.php');

        if (empty($migrationFiles)) {
            $this->warn("Tidak ada file migrasi ditemukan.");
            return;
        }

        $migrator = new \TheFramework\App\Schema\Migrator();
        try {
            $migrator->ensureTableExists();
        } catch (Throwable $e) {
            $this->error("Koneksi Database Gagal: " . $e->getMessage());
            return;
        }

        $ran = $migrator->getRan();
        $pending = [];
        foreach ($migrationFiles as $file) {
            $base = basename($file, '.php');
            if (!in_array($base, $ran)) {
                $pending[] = $file;
            }
        }

        if (empty($pending)) {
            $this->success("Database sudah up to date.");
            return;
        }

        $this->info("Ditemukan " . count($pending) . " migrasi baru.");
        if (!$this->confirm("Jalankan migrasi sekarang?", true)) {
            $this->comment("Migrasi dibatalkan.");
            return;
        }

        $batch = $migrator->getNextBatchNumber();
        sort($pending);

        foreach ($pending as $file) {
            $baseName = basename($file, '.php');
            require_once $file;
            
            $migrationClass = 'Database\\Migrations\\Migration_' . $baseName;

            if (class_exists($migrationClass)) {
                try {
                    $this->comment("Migrating: $baseName");
                    $migration = new $migrationClass();
                    
                    $start = microtime(true);
                    $migration->up();
                    $migrator->log($baseName, $batch);
                    $duration = round((microtime(true) - $start) * 1000, 2);

                    echo self::COLOR_GREEN . "  DONE " . self::COLOR_RESET . "$baseName ($duration ms)" . PHP_EOL;
                } catch (Throwable $e) {
                    $this->error("Gagal pada $baseName: " . $e->getMessage());
                    return;
                }
            } else {
                $this->warn("Class '$migrationClass' tidak ditemukan di file.");
            }
        }

        $this->success("Semua migrasi berhasil dijalankan (Batch $batch).");
    }
}

<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Throwable;

class MigrateResetCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'migrate:reset';
    }

    public function getDescription(): string
    {
        return 'Rollback SEMUA migrasi database (menjalankan down() dari semua migrasi)';
    }

    public function handle(array $args): void
    {
        $migrator = new \TheFramework\App\Schema\Migrator();

        try {
            $migrator->ensureTableExists();
        } catch (Throwable $e) {
            $this->error("Koneksi Database Gagal: " . $e->getMessage());
            return;
        }

        $ran = $migrator->getRan();
        if (empty($ran)) {
            $this->success("Tidak ada migrasi yang perlu di-reset.");
            return;
        }

        $this->warn("Ini akan me-rollback SEMUA " . count($ran) . " migrasi.");
        if (!$this->confirm("Lanjutkan?", false)) {
            $this->comment("Operasi dibatalkan.");
            return;
        }

        $this->info("Resetting " . count($ran) . " migrasi...");
        
        try {
            $count = $migrator->reset();
            foreach ($migrator->getOutput() as $line) {
                $this->line("  " . $line);
            }
            $this->success("Reset selesai. $count migrasi berhasil di-rollback.");
        } catch (Throwable $e) {
            $this->error("Gagal reset: " . $e->getMessage());
        }
    }
}

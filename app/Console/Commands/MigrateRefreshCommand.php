<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Throwable;

class MigrateRefreshCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'migrate:refresh';
    }

    public function getDescription(): string
    {
        return 'Rollback semua migrasi lalu jalankan ulang (reset + migrate)';
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

        $this->warn("Ini akan me-reset lalu menjalankan ulang semua migrasi.");
        if (!$this->confirm("Lanjutkan?", false)) {
            $this->comment("Operasi dibatalkan.");
            return;
        }

        $this->info("Refreshing database...");

        try {
            $count = $migrator->refresh();
            foreach ($migrator->getOutput() as $line) {
                $this->line("  " . $line);
            }
            $this->success("Refresh selesai. $count migrasi berhasil dijalankan ulang.");
        } catch (Throwable $e) {
            $this->error("Gagal refresh: " . $e->getMessage());
        }

        // Check for --seed flag
        if (in_array('--seed', $args)) {
            $this->info("Menjalankan seeder...");
            $seeder = new SeedCommand();
            $seeder->run([]);
        }
    }
}

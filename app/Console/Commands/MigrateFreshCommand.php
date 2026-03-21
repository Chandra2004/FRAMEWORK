<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use TheFramework\App\Core\Config;
use Throwable;

class MigrateFreshCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'migrate:fresh';
    }

    public function getDescription(): string
    {
        return 'Hapus SEMUA tabel lalu jalankan ulang migrasi dari awal';
    }

    public function handle(array $args): void
    {
        Config::loadEnv();
        if (Config::get('APP_ENV') === 'production' && !in_array('--force', $args)) {
            $this->error("migrate:fresh DIBLOKIR di production! Gunakan --force untuk override.");
            return;
        }

        $this->warn("Ini akan MENGHAPUS semua tabel dan menjalankan ulang semua migrasi.");
        $this->warn("Semua data akan HILANG!");

        if (!$this->confirm("Lanjutkan?", false)) {
            $this->comment("Operasi dibatalkan.");
            return;
        }

        $migrator = new \TheFramework\App\Schema\Migrator();

        try {
            $this->info("Dropping all tables...");
            $migrator->dropAllTables();

            foreach ($migrator->getOutput() as $line) {
                $this->line("  " . $line);
            }
            $migrator->clearOutput();

            $this->info("Running all migrations...");
            $count = $migrator->run();

            foreach ($migrator->getOutput() as $line) {
                $this->line("  " . $line);
            }

            // Safe Clear Uploads (Sync Engine)
            $this->info("Clearing allowed upload folders...");
            $fileCount = \TheFramework\Helpers\Helper::clear_uploads();
            $this->line("  ✔ $fileCount files/folders removed from allowed directories.");

            $this->success("Fresh migration selesai. $count migrasi dijalankan.");
        } catch (Throwable $e) {
            $this->error("Gagal: " . $e->getMessage());
            return;
        }

        // Check for --seed flag
        if (in_array('--seed', $args)) {
            $this->info("Menjalankan seeder...");
            $seeder = new SeedCommand();
            $seeder->run([]);
        }
    }
}

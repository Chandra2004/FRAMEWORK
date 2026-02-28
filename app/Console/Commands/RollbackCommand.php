<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Throwable;

class RollbackCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'migrate:rollback';
    }

    public function getDescription(): string
    {
        return 'Rollback batch migrasi terakhir (menjalankan down() secara berurutan)';
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

        // Parse --step=N flag
        $steps = 1;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--step=')) {
                $steps = max(1, (int) substr($arg, 7));
            }
        }

        $migrationsToRollback = $migrator->getLastBatch();

        if (empty($migrationsToRollback)) {
            $this->success("Tidak ada migrasi untuk di-rollback.");
            return;
        }

        $this->info("Rolling back " . count($migrationsToRollback) . " migrasi (batch terakhir)...");

        try {
            $count = $migrator->rollback($steps);

            echo PHP_EOL;
            foreach ($migrator->getOutput() as $line) {
                // Determine color based on content
                $color = self::COLOR_GRAY;
                if (str_contains($line, '✅'))
                    $color = self::COLOR_GREEN;
                elseif (str_contains($line, '❌'))
                    $color = self::COLOR_RED;
                elseif (str_contains($line, '⚠️'))
                    $color = self::COLOR_YELLOW;

                $this->line("  " . $line, $color);
            }

            echo PHP_EOL;
            $this->success("Rollback selesai. $count migrasi berhasil di-rollback.");
        } catch (Throwable $e) {
            $this->error("Gagal rollback: " . $e->getMessage());
        }
    }
}

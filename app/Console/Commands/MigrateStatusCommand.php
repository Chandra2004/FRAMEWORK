<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;
use Throwable;

class MigrateStatusCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'migrate:status';
    }

    public function getDescription(): string
    {
        return 'Menampilkan status setiap migrasi (Ran/Pending)';
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

        $status = $migrator->getStatus();

        if (empty($status)) {
            $this->warn("Tidak ada file migrasi ditemukan.");
            return;
        }

        $rows = [];
        foreach ($status as $s) {
            $statusLabel = $s['status'] === 'Ran'
                ? self::COLOR_GREEN . '● Ran' . self::COLOR_RESET
                : self::COLOR_YELLOW . '○ Pending' . self::COLOR_RESET;

            $rows[] = [
                $statusLabel,
                $s['migration'],
                $s['batch'] ?? '—',
            ];
        }

        $this->table(['Status', 'Migration', 'Batch'], $rows);

        $pending = count(array_filter($status, fn($s) => $s['status'] === 'Pending'));
        $ran = count($status) - $pending;

        echo PHP_EOL;
        $this->info("Total: {$ran} ran, {$pending} pending.");
    }
}

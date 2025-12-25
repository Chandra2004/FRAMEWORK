<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\CommandInterface;
use Throwable;

class MigrateCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'migrate';
    }

    public function getDescription(): string
    {
        return 'Menjalankan migrasi database';
    }

    public function run(array $args): void
    {
        $migrationDir = BASE_PATH . '/database/migrations/';
        $migrationFiles = glob($migrationDir . '*.php');

        if (empty($migrationFiles)) {
            echo "\033[38;5;214m⚠ WARNING  Tidak ada file migrasi ditemukan di $migrationDir\033[0m\n";
            return;
        }

        // Init Migrator
        $migrator = new \TheFramework\App\Migrator();
        $migrator->ensureTableExists();

        // Ambil migrasi yang sudah pernah dijalankan dari DB
        $ran = $migrator->getRan();

        // Cari file yang belum dijalankan (Pending)
        $pendingMigrations = [];
        foreach ($migrationFiles as $file) {
            $baseName = basename($file, '.php');
            if (!in_array($baseName, $ran)) {
                $pendingMigrations[] = $file;
            }
        }

        if (empty($pendingMigrations)) {
            echo "\033[38;5;28m➤ INFO  Tidak ada migrasi baru (Database up to date).\033[0m\n";
            return;
        }

        echo "\033[38;5;39m➤ INFO  Menjalankan " . count($pendingMigrations) . " migrasi baru...";
        echo "\033[0m\n";

        // Tentukan Batch ID baru
        $batch = $migrator->getNextBatchNumber();

        // Urutkan berdasarkan waktu (nama file)
        usort($pendingMigrations, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        foreach ($pendingMigrations as $file) {
            $baseName = basename($file, '.php');
            require_once $file;

            // Nama class harus sesuai konvensi: 2024_01_01_users -> Database\Migrations\Migration_2024_01_01_users
            // Tapi karena user mungkin pakai nama custom, kita coba guess nama class
            // Asumsi: Class name = 'Migration_' . filename (seperti di command make:migration)
            // Atau kita bisa parse file content untuk cari class name, tapi itu berat.
            // Mari kita standarkan: Class name di file migrasi harus "Migration_{filename}" 
            // ATAU kita bisa pakai return new class di file migrasi (anonymous class) ala Laravel 8+.
            // Untuk sekarang kita pakai standar Framework ini:
            $migrationClass = 'Database\\Migrations\\Migration_' . $baseName;

            if (class_exists($migrationClass)) {
                try {
                    $migration = new $migrationClass();
                    echo "\033[38;5;39m➤ INFO  Processing: $baseName\033[0m\n";

                    $migration->up();

                    // Catat ke DB bahwa ini sukses
                    $migrator->log($baseName, $batch);

                    echo "\033[38;5;28m  ✓ Done\033[0m\n";
                } catch (Throwable $e) {
                    echo "\033[38;5;124m✖ ERROR  Gagal pada $baseName: " . $e->getMessage() . "\033[0m\n";
                    return; // Stop jika ada satu yang gagal
                }
            } else {
                echo "\033[38;5;214m⚠ SKIPPED  Class '$migrationClass' tidak ditemukan di file.\033[0m\n";
            }
        }

        echo "\033[38;5;28m★ SUCCESS  Semua migrasi berhasil dijalankan.\033[0m\n";
    }
}

<?php
namespace TheFramework\Console\Commands;

use Throwable;
use TheFramework\Console\CommandInterface;

class RollbackCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'migrate:rollback';
    }

    public function getDescription(): string
    {
        return 'Membatalkan migrasi database dengan menjalankan method down() dari file migrasi secara berurutan (dari terbaru ke terlama).';
    }

    public function run(array $args): void
    {
        // 1. Konfirmasi User
        // $this->warn("Apakah kamu setuju untuk rollback batch migrasi terakhir? (y/n): ");
        // $handle = fopen("php://stdin", "r");
        // $response = trim(fgets($handle));
        // fclose($handle);
        // if (strtolower($response) !== 'y') {
        //     $this->info("Rollback dibatalkan.");
        //     return;
        // }
        // (Opsional: Bisa dihilangkan jika ingin cepat)

        $migrator = new \TheFramework\App\Migrator();
        $migrator->ensureTableExists();

        // 2. Dapatkan daftar migrasi batch terakhir dari DB
        $migrationsToRollback = $migrator->getLastBatch();

        if (empty($migrationsToRollback)) {
            $this->info("Tidak ada migrasi untuk di-rollback.");
            return;
        }

        $this->infoWait("Rolling back " . count($migrationsToRollback) . " migrasi");

        foreach ($migrationsToRollback as $migrationName) {
            $file = BASE_PATH . '/database/migrations/' . $migrationName . '.php';

            if (file_exists($file)) {
                require_once $file;
                $migrationClass = 'Database\\Migrations\\Migration_' . $migrationName;

                if (class_exists($migrationClass)) {
                    try {
                        $migration = new $migrationClass();
                        $this->info("Rollback: $migrationName...");

                        $migration->down();

                        // Hapus dari log DB
                        $migrator->delete($migrationName);

                        echo "\033[38;5;28m  ✓ Done\033[0m\n";
                    } catch (Throwable $e) {
                        $this->error("Gagal rollback $migrationName: " . $e->getMessage());
                        // Kita stop rollback jika error, agar state tidak inkonsisten
                        return;
                    }
                } else {
                    $this->error("Class $migrationClass tidak ditemukan.");
                }
            } else {
                $this->warn("File migrasi tidak ditemukan: $file. Menghapus record dari database saja.");
                // Jika file hilang, kita asumsikan user ingin menghapus jejaknya dari DB
                $migrator->delete($migrationName);
            }
        }

        $this->success("Rollback selesai.");
    }

    /**
     * Output helper mirip Laravel
     */
    private function info(string $message): void
    {
        echo "\033[38;5;39m➤ INFO  {$message}\033[0m\n";
    }

    private function infoWait(string $message): void
    {
        echo "\033[38;5;39m➤ INFO  {$message}";
        for ($i = 0; $i < 3; $i++) {
            echo ".";
            usleep(200000);
        }
        echo "\033[0m\n";
    }

    private function warn(string $message): void
    {
        echo "\033[38;5;214m⚠ WARNING  {$message}\033[0m";
    }

    private function error(string $message): void
    {
        echo "\033[38;5;124m✖ ERROR  {$message}\033[0m\n";
    }

    private function success(string $message): void
    {
        echo "\033[38;5;28m★ SUCCESS  {$message}\033[0m\n";
    }
}

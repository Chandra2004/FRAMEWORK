<?php

namespace TheFramework\App\Schema;

use TheFramework\App\Database\Database;
use TheFramework\App\Core\Config;

/**
 * Migrator — Migration Tracker & Executor
 * 
 * Sistem migrasi lengkap: tracking, executing, rollback, reset, dan status.
 * Terinspirasi dari Laravel Migrator, ditambah fitur ekstra.
 * 
 * @package TheFramework\App\Schema
 * @version 5.0.1
 */
class Migrator
{
    protected Database $db;
    protected string $table = 'migrations';
    protected string $migrationPath;
    protected array $output = [];
    protected bool $pretending = false;

    public function __construct(?string $migrationPath = null)
    {
        $this->db = Database::getInstance();
        $this->migrationPath = $migrationPath ?: $this->getDefaultPath();
    }

    /**
     * Default migration path
     */
    protected function getDefaultPath(): string
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 3);
        return $root . '/database/migrations';
    }

    /**
     * Set custom migration path
     */
    public function setPath(string $path): static
    {
        $this->migrationPath = $path;
        return $this;
    }

    /**
     * Get current migration path
     */
    public function getPath(): string
    {
        return $this->migrationPath;
    }

    /**
     * Get collected output messages
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * Clear output messages
     */
    public function clearOutput(): void
    {
        $this->output = [];
    }

    /**
     * Add message to output
     */
    protected function note(string $message): void
    {
        $this->output[] = $message;
    }

    // ========================================================
    //  MIGRATIONS TABLE MANAGEMENT
    // ========================================================

    /**
     * Pastikan tabel migrations ada
     */
    public function ensureTableExists(): void
    {
        try {
            $this->db->query("SELECT 1 FROM `{$this->table}` LIMIT 1;");
            $this->db->execute();
        } catch (\Throwable) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->db->query($sql);
            $this->db->execute();
            $this->note("✅ Tabel '{$this->table}' berhasil dibuat.");
        }
    }

    /**
     * Reset tabel migrations (hapus semua records)
     */
    public function resetTable(): void
    {
        $this->ensureTableExists();
        $this->db->query("TRUNCATE TABLE `{$this->table}`;");
        $this->db->execute();
    }

    // ========================================================
    //  MIGRATION FILE MANAGEMENT
    // ========================================================

    /**
     * Scan folder migrations dan dapatkan semua file migrasi
     * 
     * @return array<string,string> [filename => absolutePath]
     */
    public function getMigrationFiles(): array
    {
        $path = $this->migrationPath;

        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;
            if (pathinfo($item, PATHINFO_EXTENSION) !== 'php')
                continue;

            $name = basename($item, '.php');
            $files[$name] = $path . DIRECTORY_SEPARATOR . $item;
        }

        // Urutkan berdasarkan nama (timestamp)
        ksort($files);
        return $files;
    }

    /**
     * Resolve file migrasi menjadi instance class
     */
    public function resolve(string $filePath): object
    {
        require_once $filePath;

        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $className = $this->resolveClassName($filename);

        if (!class_exists($className)) {
            throw new \RuntimeException("Class '$className' tidak ditemukan di file: $filePath");
        }

        return new $className();
    }

    /**
     * Convert nama file migrasi ke fully-qualified class name
     * 
     * Convention: "2025_08_14_045829_CreateUsersTable" 
     *           → "Database\Migrations\Migration_2025_08_14_045829_CreateUsersTable"
     */
    protected function resolveClassName(string $filename): string
    {
        // Hapus ekstensi .php jika ada
        $filename = str_replace('.php', '', $filename);

        return 'Database\\Migrations\\Migration_' . $filename;
    }

    // ========================================================
    //  MIGRATION TRACKING (QUERY)
    // ========================================================

    /**
     * Dapatkan daftar migrasi yang sudah dijalankan
     */
    public function getRan(): array
    {
        $this->ensureTableExists();
        $this->db->query("SELECT `migration` FROM `{$this->table}` ORDER BY `batch`, `id`;");
        $rows = $this->db->resultSet();
        return array_column($rows, 'migration');
    }

    /**
     * Catat migrasi yang sudah dijalankan
     */
    public function log(string $file, int $batch): void
    {
        $this->db->query("INSERT INTO `{$this->table}` (`migration`, `batch`) VALUES (:migration, :batch);");
        $this->db->bind(':migration', $file);
        $this->db->bind(':batch', $batch);
        $this->db->execute();
    }

    /**
     * Hapus record migrasi (saat rollback)
     */
    public function delete(string $file): void
    {
        $this->db->query("DELETE FROM `{$this->table}` WHERE `migration` = :migration;");
        $this->db->bind(':migration', $file);
        $this->db->execute();
    }

    /**
     * Dapatkan batch number berikutnya
     */
    public function getNextBatchNumber(): int
    {
        $this->ensureTableExists();
        $this->db->query("SELECT MAX(`batch`) AS `last_batch` FROM `{$this->table}`;");
        $row = $this->db->single();
        return ($row['last_batch'] ?? 0) + 1;
    }

    /**
     * Dapatkan migrasi dari batch terakhir (untuk rollback)
     */
    public function getLastBatch(): array
    {
        $this->ensureTableExists();
        $this->db->query("SELECT MAX(`batch`) AS `last_batch` FROM `{$this->table}`;");
        $row = $this->db->single();
        $lastBatch = $row['last_batch'] ?? 0;

        if ($lastBatch < 1)
            return [];

        $this->db->query("SELECT `migration` FROM `{$this->table}` WHERE `batch` = :batch ORDER BY `id` DESC;");
        $this->db->bind(':batch', $lastBatch);
        $rows = $this->db->resultSet();

        return array_map(fn($r) => $r['migration'], $rows);
    }

    /**
     * Dapatkan migrasi berdasarkan batch tertentu
     */
    public function getByBatch(int $batch): array
    {
        $this->ensureTableExists();
        $this->db->query("SELECT `migration` FROM `{$this->table}` WHERE `batch` = :batch ORDER BY `id` DESC;");
        $this->db->bind(':batch', $batch);
        $rows = $this->db->resultSet();
        return array_map(fn($r) => $r['migration'], $rows);
    }

    // ========================================================
    //  🚀 MIGRATION EXECUTION
    // ========================================================

    /**
     * Jalankan semua migrasi yang belum dijalankan
     * 
     * @return int Jumlah migrasi yang dijalankan
     */
    public function run(): int
    {
        $this->ensureTableExists();

        $files = $this->getMigrationFiles();
        $ran = $this->getRan();
        $batch = $this->getNextBatchNumber();
        $count = 0;

        foreach ($files as $filename => $path) {
            if (in_array($filename, $ran))
                continue;

            $this->runUp($filename, $path, $batch);
            $count++;
        }

        if ($count === 0) {
            $this->note('ℹ️  Tidak ada migrasi baru untuk dijalankan.');
        } else {
            $this->note("✅ $count migrasi berhasil dijalankan (Batch #$batch).");
        }

        return $count;
    }

    /**
     * Jalankan satu migrasi (UP)
     */
    public function runUp(string $filename, string $path, int $batch): void
    {
        $this->note("⬆️  Migrating: $filename");
        $startTime = microtime(true);

        try {
            $migration = $this->resolve($path);

            if (!method_exists($migration, 'up')) {
                throw new \RuntimeException("Method up() tidak ditemukan di migrasi: $filename");
            }

            if ($this->pretending) {
                $this->note("   [PRETEND] Would run up() on $filename");
            } else {
                $migration->up();
                $this->log($filename, $batch);
            }

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            $this->note("   ✅ Migrated: $filename ({$elapsed}ms)");
        } catch (\Throwable $e) {
            $this->note("   ❌ Failed: $filename — " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Jalankan satu migrasi (DOWN / rollback)
     */
    public function runDown(string $filename, string $path): void
    {
        $this->note("⬇️  Rolling back: $filename");
        $startTime = microtime(true);

        try {
            $migration = $this->resolve($path);

            if (!method_exists($migration, 'down')) {
                throw new \RuntimeException("Method down() tidak ditemukan di migrasi: $filename");
            }

            if ($this->pretending) {
                $this->note("   [PRETEND] Would run down() on $filename");
            } else {
                $migration->down();
                $this->delete($filename);
            }

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            $this->note("   ✅ Rolled back: $filename ({$elapsed}ms)");
        } catch (\Throwable $e) {
            $this->note("   ❌ Rollback failed: $filename — " . $e->getMessage());
            throw $e;
        }
    }

    // ========================================================
    //  ROLLBACK & RESET
    // ========================================================

    /**
     * Rollback batch terakhir
     * 
     * @param int $steps Jumlah batch yang di-rollback (default 1)
     * @return int Jumlah migrasi yang di-rollback
     */
    public function rollback(int $steps = 1): int
    {
        $this->ensureTableExists();
        $files = $this->getMigrationFiles();
        $count = 0;

        for ($i = 0; $i < $steps; $i++) {
            $migrations = $this->getLastBatch();

            if (empty($migrations)) {
                $this->note('ℹ️  Tidak ada migrasi untuk di-rollback.');
                break;
            }

            foreach ($migrations as $filename) {
                if (isset($files[$filename])) {
                    $this->runDown($filename, $files[$filename]);
                    $count++;
                } else {
                    $this->note("⚠️  File tidak ditemukan: $filename — record dihapus.");
                    $this->delete($filename);
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $this->note("✅ $count migrasi berhasil di-rollback.");
        }

        return $count;
    }

    /**
     * Rollback SEMUA migrasi
     */
    public function reset(): int
    {
        $this->ensureTableExists();
        $files = $this->getMigrationFiles();
        $ran = array_reverse($this->getRan()); // Rollback dari yang terakhir
        $count = 0;

        foreach ($ran as $filename) {
            if (isset($files[$filename])) {
                $this->runDown($filename, $files[$filename]);
                $count++;
            } else {
                $this->note("⚠️  File tidak ditemukan: $filename — record dihapus.");
                $this->delete($filename);
                $count++;
            }
        }

        if ($count > 0) {
            $this->note("✅ $count migrasi berhasil di-reset.");
        }

        return $count;
    }

    /**
     * Drop semua tabel lalu jalankan ulang semua migrasi (migrate:fresh)
     */
    public function fresh(): int
    {
        $this->note('🔄 Dropping all tables...');
        $this->dropAllTables();
        $this->note('✅ All tables dropped.');

        return $this->run();
    }

    /**
     * Reset lalu jalankan ulang (migrate:refresh)
     */
    public function refresh(): int
    {
        $this->note('🔄 Resetting all migrations...');
        $this->reset();

        return $this->run();
    }

    // ========================================================
    //  DROP ALL TABLES
    // ========================================================

    /**
     * Drop SEMUA tabel di database
     */
    public function dropAllTables(): void
    {
        // Safety check environment
        if (class_exists(Config::class)) {
            $env = Config::get('APP_ENV', 'production');
            if ($env === 'production') {
                throw new \RuntimeException(
                    "❌ BAHAYA: dropAllTables() tidak boleh dijalankan di production! " .
                    "Set APP_ENV ke 'local' atau 'testing' terlebih dahulu."
                );
            }
        }

        $this->db->query('SHOW TABLES;');
        $tables = $this->db->resultSet();

        if (empty($tables)) {
            $this->note('ℹ️  Database sudah kosong.');
            return;
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 0;');
        $this->db->execute();

        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $this->db->query("DROP TABLE IF EXISTS `{$tableName}`;");
            $this->db->execute();
            $this->note("   🗑️  Dropped: $tableName");
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1;');
        $this->db->execute();
    }

    /**
     * Drop semua views
     */
    public function dropAllViews(): void
    {
        $this->db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW';");
        $views = $this->db->resultSet();

        foreach ($views as $view) {
            $viewName = array_values($view)[0];
            $this->db->query("DROP VIEW IF EXISTS `{$viewName}`;");
            $this->db->execute();
            $this->note("   🗑️  Dropped view: $viewName");
        }
    }

    // ========================================================
    //  STATUS & REPORTING
    // ========================================================

    /**
     * Dapatkan status semua migrasi (ran/pending)
     * 
     * @return array [ ['migration' => '...', 'batch' => 1, 'status' => 'Ran'], ... ]
     */
    public function getStatus(): array
    {
        $this->ensureTableExists();

        $files = $this->getMigrationFiles();
        $ran = $this->getRanWithBatch();
        $status = [];

        foreach ($files as $filename => $path) {
            if (isset($ran[$filename])) {
                $status[] = [
                    'migration' => $filename,
                    'batch' => $ran[$filename]['batch'],
                    'status' => 'Ran',
                    'ran_at' => $ran[$filename]['executed_at'] ?? null,
                ];
            } else {
                $status[] = [
                    'migration' => $filename,
                    'batch' => null,
                    'status' => 'Pending',
                    'ran_at' => null,
                ];
            }
        }

        return $status;
    }

    /**
     * Dapatkan migrasi yang sudah dijalankan beserta batch & timestamp
     */
    protected function getRanWithBatch(): array
    {
        $this->db->query("SELECT `migration`, `batch`, `executed_at` FROM `{$this->table}` ORDER BY `id`;");
        $rows = $this->db->resultSet();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['migration']] = $row;
        }
        return $result;
    }

    /**
     * Dapatkan jumlah migrasi pending (belum dijalankan)
     */
    public function getPendingCount(): int
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRan();
        $pending = 0;

        foreach ($files as $filename => $path) {
            if (!in_array($filename, $ran)) {
                $pending++;
            }
        }

        return $pending;
    }

    /**
     * Dapatkan daftar file migrasi yang pending
     */
    public function getPendingMigrations(): array
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRan();
        $pending = [];

        foreach ($files as $filename => $path) {
            if (!in_array($filename, $ran)) {
                $pending[$filename] = $path;
            }
        }

        return $pending;
    }

    // ========================================================
    //  PRETEND MODE (Dry Run)
    // ========================================================

    /**
     * Aktifkan pretend mode (dry-run, tidak benar-benar menjalankan SQL)
     */
    public function pretend(): static
    {
        $this->pretending = true;
        return $this;
    }

    /**
     * Matikan pretend mode
     */
    public function stopPretending(): static
    {
        $this->pretending = false;
        return $this;
    }

    /**
     * Cek apakah sedang pretend mode
     */
    public function isPretending(): bool
    {
        return $this->pretending;
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Jalankan migrasi spesifik (by filename)
     */
    public function runSpecific(string $filename): bool
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRan();

        if (!isset($files[$filename])) {
            $this->note("❌ File migrasi tidak ditemukan: $filename");
            return false;
        }

        if (in_array($filename, $ran)) {
            $this->note("ℹ️  Migrasi sudah pernah dijalankan: $filename");
            return false;
        }

        $batch = $this->getNextBatchNumber();
        $this->runUp($filename, $files[$filename], $batch);
        return true;
    }

    /**
     * Rollback migrasi spesifik (by filename)
     */
    public function rollbackSpecific(string $filename): bool
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRan();

        if (!in_array($filename, $ran)) {
            $this->note("ℹ️  Migrasi belum dijalankan: $filename");
            return false;
        }

        if (isset($files[$filename])) {
            $this->runDown($filename, $files[$filename]);
        } else {
            $this->note("⚠️  File tidak ditemukan, hanya menghapus record: $filename");
            $this->delete($filename);
        }
        return true;
    }

    /**
     * Generate file migrasi baru
     * 
     * @return string Path ke file yang dibuat
     */
    public function createMigrationFile(string $name, ?string $table = null, bool $create = false): string
    {
        $path = $this->migrationPath;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $timestamp = date('Y_m_d_His');
        $snakeName = strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($name)));
        $snakeName = preg_replace('/[^a-z0-9_]/', '_', $snakeName);
        $filename = "{$timestamp}_{$snakeName}.php";
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;

        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $snakeName)));

        if ($create && $table) {
            $stub = $this->getCreateStub($className, $table);
        } elseif ($table) {
            $stub = $this->getTableStub($className, $table);
        } else {
            $stub = $this->getBlankStub($className);
        }

        file_put_contents($filepath, $stub);
        $this->note("✅ Migrasi dibuat: $filename");

        return $filepath;
    }

    protected function getCreateStub(string $className, string $table): string
    {
        return <<<PHP
<?php

use TheFramework\\App\\Schema\\Schema;
use TheFramework\\App\\Schema\\Blueprint;
use TheFramework\\Database\\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            // Tambahkan kolom di sini
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
}
PHP;
    }

    protected function getTableStub(string $className, string $table): string
    {
        return <<<PHP
<?php

use TheFramework\\App\\Schema\\Schema;
use TheFramework\\App\\Schema\\Blueprint;
use TheFramework\\Database\\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            // Modifikasi tabel di sini
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            // Rollback modifikasi di sini
        });
    }
}
PHP;
    }

    protected function getBlankStub(string $className): string
    {
        return <<<PHP
<?php

use TheFramework\\App\\Schema\\Schema;
use TheFramework\\App\\Schema\\Blueprint;
use TheFramework\\Database\\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
}
PHP;
    }

    /**
     * Dapatkan ringkasan migrasi sebagai formatted string
     */
    public function summary(): string
    {
        $status = $this->getStatus();
        $ran = 0;
        $pending = 0;
        foreach ($status as $s) {
            if ($s['status'] === 'Ran')
                $ran++;
            else
                $pending++;
        }

        $lines = [];
        $lines[] = "📊 Migration Summary";
        $lines[] = str_repeat('─', 50);
        $lines[] = "   Total:   " . count($status);
        $lines[] = "   Ran:     $ran";
        $lines[] = "   Pending: $pending";
        $lines[] = str_repeat('─', 50);

        foreach ($status as $s) {
            $icon = $s['status'] === 'Ran' ? '✅' : '⏳';
            $batch = $s['batch'] ? " [Batch #{$s['batch']}]" : '';
            $lines[] = "   $icon {$s['migration']}{$batch}";
        }

        return implode("\n", $lines);
    }

    /**
     * Squash: Export semua migrasi yang sudah dijalankan ke satu SQL dump file
     * 
     * @return string Path ke file dump
     */
    public function squash(): string
    {
        $path = $this->migrationPath;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $tables = Schema::getTables();
        $dumpLines = [];
        $dumpLines[] = "-- Squashed migration dump";
        $dumpLines[] = "-- Generated: " . date('Y-m-d H:i:s');
        $dumpLines[] = "-- Tables: " . count($tables);
        $dumpLines[] = "";
        $dumpLines[] = "SET FOREIGN_KEY_CHECKS = 0;";
        $dumpLines[] = "";

        foreach ($tables as $table) {
            if ($table === $this->table)
                continue; // Skip migrations table
            $createSql = Schema::getCreateTable($table);
            if ($createSql) {
                $dumpLines[] = "DROP TABLE IF EXISTS `$table`;";
                $dumpLines[] = $createSql . ";";
                $dumpLines[] = "";
            }
        }

        $dumpLines[] = "SET FOREIGN_KEY_CHECKS = 1;";

        $filename = date('Y_m_d_His') . '_squashed_schema.sql';
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filepath, implode("\n", $dumpLines));

        $this->note("✅ Schema squashed ke: $filename");
        return $filepath;
    }
}

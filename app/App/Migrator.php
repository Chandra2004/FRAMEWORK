<?php

namespace TheFramework\App;

class Migrator
{
    protected $db;
    protected $table = 'migrations';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Pastikan tabel migrations ada.
     */
    public function ensureTableExists()
    {
        // Cek apakah tabel sudah ada dengan query sederhana
        try {
            $this->db->query("SELECT 1 FROM {$this->table} LIMIT 1");
            $this->db->execute(); // Jika tidak throw error, berarti ada
        } catch (\Throwable $e) {
            // Jika error (kemungkinan table not found), kita buat
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $this->db->query($sql);
            $this->db->execute();
        }
    }

    /**
     * Ambil array nama file migrasi yang sudah dijalankan.
     */
    public function getRan(): array
    {
        $this->ensureTableExists();
        $this->db->query("SELECT migration FROM {$this->table}");
        $rows = $this->db->resultSet();

        return array_column($rows, 'migration');
    }

    /**
     * Catat migrasi yang barusan dijalankan.
     */
    public function log(string $file, int $batch)
    {
        $this->db->query("INSERT INTO {$this->table} (migration, batch) VALUES (:migration, :batch)");
        $this->db->bind(':migration', $file);
        $this->db->bind(':batch', $batch);
        $this->db->execute();
    }

    /**
     * Hapus catatan migrasi (saat rollback).
     */
    public function delete(string $file)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE migration = :migration");
        $this->db->bind(':migration', $file);
        $this->db->execute();
    }

    /**
     * Dapatkan batch number berikutnya.
     */
    public function getNextBatchNumber(): int
    {
        $this->ensureTableExists();
        $this->db->query("SELECT MAX(batch) as last_batch FROM {$this->table}");
        $row = $this->db->single();

        return ($row['last_batch'] ?? 0) + 1;
    }

    /**
     * Dapatkan daftar migrasi dari batch terakhir untuk di-rollback.
     */
    public function getLastBatch(): array
    {
        $this->ensureTableExists();
        $this->db->query("SELECT MAX(batch) as last_batch FROM {$this->table}");
        $row = $this->db->single();
        $lastBatch = $row['last_batch'] ?? 0;

        if ($lastBatch < 1) {
            return [];
        }

        $this->db->query("SELECT migration FROM {$this->table} WHERE batch = :batch ORDER BY id DESC");
        $this->db->bind(':batch', $lastBatch);
        $rows = $this->db->resultSet();

        return array_map(fn($r) => $r['migration'], $rows);
    }

    /**
     * Hapus semua tabel di database saat ini.
     */
    public function dropAllTables()
    {
        $this->db->query("SHOW TABLES");
        $tables = $this->db->resultSet();

        if (empty($tables)) {
            return;
        }

        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->execute();

        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $this->db->query("DROP TABLE IF EXISTS `{$tableName}`");
            $this->db->execute();
        }

        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        $this->db->execute();
    }
}

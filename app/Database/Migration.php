<?php

namespace TheFramework\Database;

use TheFramework\App\Database\Database;
use TheFramework\App\Schema\Schema;

/**
 * Migration Base Class - v5.0
 * Memberikan fondasi kuat untuk manipulasi skema database.
 */
abstract class Migration
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Jalankan migrasi
     */
    abstract public function up(): void;

    /**
     * Batalkan migrasi
     */
    abstract public function down(): void;

    /**
     * Helper: Mulai transaksi database
     */
    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Helper: Simpan perubahan (Commit)
     */
    protected function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Helper: Batalkan perubahan (Rollback)
     */
    protected function rollback(): void
    {
        $this->db->rollBack();
    }
}

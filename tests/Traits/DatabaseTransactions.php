<?php

namespace Tests\Traits;

use TheFramework\App\Database\Database;

trait DatabaseTransactions
{
    /**
     * Jalankan traksaksi di setiap test untuk rollback perubahan otomatis.
     */
    protected function beginDatabaseTransaction()
    {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();
        } catch (\Throwable $e) {
            // Abaikan jika database belum siap
        }
    }

    /**
     * Rollback transaksi setelah test selesai.
     */
    protected function rollBackDatabaseTransaction()
    {
        try {
            $db = Database::getInstance();
            $db->rollBack();
        } catch (\Throwable $e) {
            // Abaikan peringatan jika tidak ada active transaction
        }
    }
}

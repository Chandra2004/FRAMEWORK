<?php

namespace TheFramework\Helpers;

use TheFramework\App\Database\Database;
use Throwable;

/**
 * Database Helper - Paten Utility
 * Mempermudah pengecekan status dan debugging database.
 */
class DatabaseHelper
{
    /**
     * Matikan koneksi database secara global.
     */
    public static function disable(): void
    {
        Database::setEnabled(false);
    }

    /**
     * Aktifkan koneksi database secara global.
     */
    public static function enable(): void
    {
        Database::setEnabled(true);
    }

    /**
     * Cek apakah fitur database diaktifkan di framework.
     */
    public static function isEnabled(): bool
    {
        return Database::isEnabled();
    }

    /**
     * Test koneksi database secara cepat.
     */
    public static function testConnection(): bool
    {
        return Database::getInstance()->testConnection();
    }

    /**
     * Test koneksi database dengan metrik waktu.
     * Mengembalikan array informasi detail.
     */
    public static function auditConnection(): array
    {
        $start = microtime(true);
        $status = false;
        $error = null;
        $version = null;

        try {
            $db = Database::getInstance();
            $status = $db->testConnection();
            if ($status) {
                $db->query("SELECT VERSION() as v");
                $version = $db->single()['v'] ?? 'Unknown';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $duration = (microtime(true) - $start) * 1000;

        return [
            'connected' => $status,
            'latency_ms' => round($duration, 2),
            'version' => $version,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

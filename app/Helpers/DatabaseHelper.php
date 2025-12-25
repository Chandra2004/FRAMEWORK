<?php

namespace TheFramework\Helpers;

use TheFramework\App\Database;

/**
 * Helper untuk mengelola database connection
 */
class DatabaseHelper
{
    /**
     * Disable database connection
     * Gunakan ini jika aplikasi tidak memerlukan database
     * 
     * @return void
     */
    public static function disable(): void
    {
        Database::setEnabled(false);
    }

    /**
     * Enable database connection
     * 
     * @return void
     */
    public static function enable(): void
    {
        Database::setEnabled(true);
    }

    /**
     * Check apakah database enabled
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Database::isEnabled();
    }

    /**
     * Check apakah database terhubung
     * 
     * @return bool
     */
    public static function isConnected(): bool
    {
        try {
            $db = Database::getInstance();
            return $db->isConnected();
        } catch (\Exception $e) {
            return false;
        }
    }
}

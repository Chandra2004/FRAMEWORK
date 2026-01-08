<?php

namespace TheFramework\Database;

use TheFramework\App\Database;

abstract class Seeder
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Method yang akan dijalankan saat seeding.
     * Developer harus meng-override method ini.
     */
    abstract public function run();

    /**
     * Memanggil seeder lain dari dalam seeder.
     * 
     * @param string|array $class Nama class seeder atau array class
     */
    public function call($class)
    {
        $classes = is_array($class) ? $class : [$class];

        foreach ($classes as $seederClass) {
            if (!class_exists($seederClass)) {
                // Coba namespace default
                $namespacedClass = "Database\\Seeders\\" . $seederClass;
                if (class_exists($namespacedClass)) {
                    $seederClass = $namespacedClass;
                }
            }

            $seeder = new $seederClass();
            if (method_exists($seeder, 'run')) {
                $seeder->run();
                echo "\033[38;5;28m✓ Seeder executed: " . $seederClass . "\033[0m\n";
            } else {
                echo "\033[38;5;124m✖ Error: Class $seederClass tidak memiliki method run()\033[0m\n";
            }
        }
    }
    protected static $table;

    /**
     * Set target table name for subsequent create calls
     */
    public static function setTable($table)
    {
        self::$table = $table;
    }

    /**
     * Static create method to insert data into the currently set table
     */
    public static function create(array $rows)
    {
        if (!self::$table) {
            throw new \Exception("Table name not set. Call Seeder::setTable('tablename') first.");
        }

        // Handle array of arrays vs single assoc array
        // Jika inputnya [[...], [...]] maka itu batch insert
        // Jika inputnya [...] saja (assoc), bungkus jadi [[...]]
        if (!isset($rows[0]) || !is_array($rows[0])) {
            $rows = [$rows];
        }

        \TheFramework\App\Schema::insert(self::$table, $rows);
    }
}

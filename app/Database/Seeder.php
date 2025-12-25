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
            $seeder = new $seederClass();
            if (method_exists($seeder, 'run')) {
                $seeder->run();
                echo "\033[38;5;28m✓ Seeder executed: " . $seederClass . "\033[0m\n";
            } else {
                echo "\033[38;5;124m✖ Error: Class $seederClass tidak memiliki method run()\033[0m\n";
            }
        }
    }
}

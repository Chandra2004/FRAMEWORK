<?php

namespace TheFramework\Database;

use TheFramework\App\Database\Database;
use TheFramework\App\Schema\Schema;
use Exception;

/**
 * Seeder Base Class - v5.0
 * Engine pengisi data database yang cerdas dan mendukung batching.
 */
abstract class Seeder
{
    protected Database $db;
    protected ?string $table = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Logika utama seeding (Wajib di-override)
     */
    abstract public function run(): void;

    /**
     * Memanggil seeder lain secara berurutan
     */
    public function call(array|string $classes): void
    {
        $classes = (array) $classes;

        foreach ($classes as $class) {
            $seederClass = $this->resolveSeederClass($class);
            
            $instance = new $seederClass();
            if (method_exists($instance, 'run')) {
                $instance->run();
                echo "\033[38;5;28m  ✓ Seeded:\033[0m " . basename(str_replace('\\', '/', $seederClass)) . PHP_EOL;
            }
        }
    }

    /**
     * Set tabel target (Fluent Interface)
     */
    public function table(string $name): self
    {
        $this->table = $name;
        return $this;
    }

    /**
     * Masukkan data ke database
     */
    public function insert(array $data): void
    {
        if (!$this->table) {
            throw new Exception("Target tabel belum ditentukan. Gunasi \$this->table('nama')->insert([...])");
        }

        // Jika data tunggal (assoc array), bungkus jadi multi-array
        if (!isset($data[0]) || !is_array($data[0])) {
            $data = [$data];
        }

        Schema::insert($this->table, $data);
    }

    /**
     * Helper untuk resolve nama class seeder
     */
    private function resolveSeederClass(string $class): string
    {
        if (class_exists($class)) return $class;
        
        $namespaced = "Database\\Seeders\\$class";
        if (class_exists($namespaced)) return $namespaced;

        throw new Exception("Seeder class [$class] tidak ditemukan.");
    }

    /**
     * Shortcut statis untuk penggunaan cepat (Legacy Support)
     */
    public static function setTable(string $table): void
    {
        (new static())->table($table);
    }
}

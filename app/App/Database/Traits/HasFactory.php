<?php

namespace TheFramework\App\Database\Traits;

use TheFramework\App\Database\Factory;
use Exception;

/**
 * HasFactory — Trait untuk Model
 *
 * Menambahkan method static `factory()` di Model agar bisa dipanggil:
 *   User::factory()->create();
 *   User::factory()->count(10)->create();
 *   User::factory()->state('admin')->make();
 *
 * Convention:
 *   Model: TheFramework\Models\User
 *   Factory: Database\Factories\UserFactory
 *
 *   Model: TheFramework\Models\Store\Product
 *   Factory: Database\Factories\Store\ProductFactory
 */
trait HasFactory
{
    /**
     * Buat instance Factory yang terhubung dengan model ini.
     *
     * @param int $count Jumlah data yang akan dibuat (default: 1)
     * @return Factory
     * @throws Exception
     */
    public static function factory(int $count = 1): Factory
    {
        $factoryClass = static::resolveFactoryName();

        if (!class_exists($factoryClass)) {
            throw new Exception(
                "Factory [{$factoryClass}] tidak ditemukan untuk model [" . static::class . "].\n" .
                "Buat dengan: php artisan make:factory " . class_basename(static::class) . "Factory"
            );
        }

        return $factoryClass::new($count);
    }

    /**
     * Resolve nama class Factory dari nama Model.
     *
     * Convention:
     *   TheFramework\Models\User         → Database\Factories\UserFactory
     *   TheFramework\Models\Store\Product → Database\Factories\Store\ProductFactory
     *
     * Anda bisa override method ini di model jika ingin custom factory path.
     *
     * @return string
     */
    protected static function resolveFactoryName(): string
    {
        // Jika model mendefinisikan $factory property secara eksplisit
        if (property_exists(static::class, 'factory') && isset(static::$factory)) {
            $factory = 'factory';
            return static::$$factory;
        }

        $modelClass = static::class;
        $basename = class_basename($modelClass);

        // Ambil sub-namespace jika ada (misal: Store\Product)
        $modelNamespace = 'TheFramework\\Models\\';
        $subNamespace = '';

        if (str_starts_with($modelClass, $modelNamespace)) {
            $relative = substr($modelClass, strlen($modelNamespace));
            $parts = explode('\\', $relative);
            array_pop($parts); // Remove class name
            if (!empty($parts)) {
                $subNamespace = implode('\\', $parts) . '\\';
            }
        }

        return 'Database\\Factories\\' . $subNamespace . $basename . 'Factory';
    }
}

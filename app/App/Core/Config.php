<?php

namespace TheFramework\App\Core;

use Dotenv\Dotenv;

/**
 * Config — Configuration Manager
 * 
 * Mendukung .env, config files PHP (dot notation), type casting,
 * runtime set/get, caching, dan validation.
 * 
 * @package TheFramework\App\Core
 * @version 5.0.1
 */
class Config
{
    /**
     * Apakah .env sudah di-load
     */
    private static bool $isLoaded = false;

    /**
     * Config items dari config files PHP
     * @var array<string, mixed>
     */
    private static array $items = [];

    /**
     * Runtime overrides
     * @var array<string, mixed>
     */
    private static array $overrides = [];

    /**
     * Root directory
     */
    private static ?string $rootDir = null;

    // ========================================================
    //  INITIALIZATION
    // ========================================================

    /**
     * Get root directory
     */
    protected static function getRootDir(): string
    {
        if (static::$rootDir === null) {
            static::$rootDir = defined('ROOT_DIR') ? ROOT_DIR
                : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
        }
        return static::$rootDir;
    }

    /**
     * Set root directory
     */
    public static function setRootDir(string $path): void
    {
        static::$rootDir = rtrim($path, '/\\');
    }

    /**
     * Load .env file dan config files
     */
    public static function loadEnv(): void
    {
        if (static::$isLoaded) {
            return;
        }

        $root = static::getRootDir();
        $storageDir = $root . DIRECTORY_SEPARATOR . 'storage';
        $cacheFile = $storageDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';

        // 1. Cek cached config
        if (file_exists($cacheFile)) {
            $config = require $cacheFile;
            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        } else {
            // 2. Parse .env file
            try {
                $dotenv = Dotenv::createImmutable($root);
                $dotenv->safeLoad();
            } catch (\Throwable) {
                // Abaikan jika .env tidak ada
            }
        }

        // 3. Load config files PHP
        static::loadConfigFiles();

        static::$isLoaded = true;
    }

    /**
     * Load semua file PHP di folder config/
     */
    protected static function loadConfigFiles(): void
    {
        $configDir = static::getRootDir() . DIRECTORY_SEPARATOR . 'config';

        if (!is_dir($configDir))
            return;

        foreach (glob($configDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
            $key = basename($file, '.php');
            $value = require $file;
            if (is_array($value)) {
                static::$items[$key] = $value;
            }
        }
    }

    // ========================================================
    //  GET / SET
    // ========================================================

    /**
     * Ambil config value
     * 
     * Support dot notation: Config::get('database.default')
     * Support .env: Config::get('APP_ENV')
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 1. Cek runtime overrides dulu
        if (array_key_exists($key, static::$overrides)) {
            return static::$overrides[$key];
        }

        // 2. Cek config files (dot notation)
        if (str_contains($key, '.')) {
            $value = static::getFromItems($key);
            if ($value !== null)
                return $value;
        }

        // 3. Cek config items (non-dotted)
        if (array_key_exists($key, static::$items)) {
            return static::$items[$key];
        }

        // 4. Cek environment variables
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && !in_array($key, ['argc', 'argv', 'REQUEST_TIME', 'REQUEST_TIME_FLOAT'])) {
            return $_SERVER[$key];
        }

        $envValue = getenv($key);
        if ($envValue !== false) {
            return $envValue;
        }

        return $default;
    }

    /**
     * Ambil dari config items via dot notation
     */
    protected static function getFromItems(string $key): mixed
    {
        $segments = explode('.', $key);
        $current = static::$items;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Set config value saat runtime
     * 
     * Support dot notation: Config::set('database.default', 'mysql')
     */
    public static function set(string $key, mixed $value): void
    {
        static::$overrides[$key] = $value;

        // Juga set di items jika dot notation
        if (str_contains($key, '.')) {
            static::setInItems($key, $value);
        }
    }

    /**
     * Set ke items array via dot notation
     */
    protected static function setInItems(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &static::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }

    /**
     * Cek apakah config key ada
     */
    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    // ========================================================
    //  TYPE-CASTED GETTERS
    // ========================================================

    /**
     * Get sebagai string
     */
    public static function getString(string $key, string $default = ''): string
    {
        return (string) static::get($key, $default);
    }

    /**
     * Get sebagai integer
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    /**
     * Get sebagai float
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        return (float) static::get($key, $default);
    }

    /**
     * Get sebagai boolean
     * Handles: 'true', '1', 'yes', 'on' → true
     *          'false', '0', 'no', 'off', '' → false
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key);
        if ($value === null)
            return $default;

        if (is_bool($value))
            return $value;
        if (is_int($value))
            return $value !== 0;

        $value = strtolower(trim((string) $value));
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Get sebagai array (split by delimiter)
     */
    public static function getArray(string $key, string $delimiter = ',', array $default = []): array
    {
        $value = static::get($key);
        if ($value === null)
            return $default;
        if (is_array($value))
            return $value;

        return array_map('trim', explode($delimiter, (string) $value));
    }

    /**
     * Get atau throw exception jika tidak ada
     * Untuk config yang WAJIB ada
     */
    public static function getOrFail(string $key): mixed
    {
        $value = static::get($key);
        if ($value === null) {
            throw new \RuntimeException("Config key '{$key}' is required but not set.");
        }
        return $value;
    }

    // ========================================================
    //  BULK OPERATIONS
    // ========================================================

    /**
     * Dapatkan semua .env values
     */
    public static function allEnv(): array
    {
        return $_ENV;
    }

    /**
     * Dapatkan semua config items (dari config files)
     */
    public static function allItems(): array
    {
        return static::$items;
    }

    /**
     * Dapatkan semua config (env + items + overrides merged)
     */
    public static function all(): array
    {
        return array_merge($_ENV, static::$items, static::$overrides);
    }

    /**
     * Get banyak key sekaligus
     */
    public static function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key => $default) {
            if (is_int($key)) {
                $result[$default] = static::get($default);
            } else {
                $result[$key] = static::get($key, $default);
            }
        }
        return $result;
    }

    // ========================================================
    //  CACHING
    // ========================================================

    /**
     * Generate config cache file
     * Gabungkan semua .env values ke satu PHP file untuk performance
     */
    public static function cache(): string
    {
        $root = static::getRootDir();
        $cacheDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'config.php';

        // Collect semua env values
        $config = [];
        foreach ($_ENV as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($cacheFile, $content, LOCK_EX);

        return $cacheFile;
    }

    /**
     * Hapus config cache
     */
    public static function clearCache(): bool
    {
        $root = static::getRootDir();
        $cacheFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';

        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return true;
    }

    /**
     * Cek apakah config cached
     */
    public static function isCached(): bool
    {
        $root = static::getRootDir();
        $cacheFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';
        return file_exists($cacheFile);
    }

    // ========================================================
    //  ENVIRONMENT DETECTION
    // ========================================================

    /**
     * Dapatkan nama environment saat ini
     */
    public static function environment(): string
    {
        return static::getString('APP_ENV', 'production');
    }

    /**
     * Cek apakah sedang di environment tertentu
     */
    public static function isEnvironment(string ...$envs): bool
    {
        return in_array(static::environment(), $envs, true);
    }

    /**
     * Cek apakah production
     */
    public static function isProduction(): bool
    {
        return static::isEnvironment('production', 'prod');
    }

    /**
     * Cek apakah local development
     */
    public static function isLocal(): bool
    {
        return static::isEnvironment('local', 'development', 'dev');
    }

    /**
     * Cek apakah testing
     */
    public static function isTesting(): bool
    {
        return static::isEnvironment('testing', 'test');
    }

    /**
     * Cek apakah debug mode aktif
     */
    public static function isDebug(): bool
    {
        return static::getBool('APP_DEBUG', false);
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Get config dengan validation
     * 
     * Contoh: Config::getValidated('PORT', fn($v) => is_numeric($v) && $v > 0, 8080)
     */
    public static function getValidated(string $key, callable $validator, mixed $default = null): mixed
    {
        $value = static::get($key, $default);
        if ($value !== null && !$validator($value)) {
            return $default;
        }
        return $value;
    }

    /**
     * Temporarily override config (untuk testing)
     * Restore otomatis setelah callback selesai
     */
    public static function withOverride(array $overrides, \Closure $callback): mixed
    {
        $original = [];
        foreach ($overrides as $key => $value) {
            $original[$key] = static::get($key);
            static::set($key, $value);
        }

        try {
            return $callback();
        } finally {
            foreach ($original as $key => $value) {
                if ($value === null) {
                    unset(static::$overrides[$key]);
                } else {
                    static::set($key, $value);
                }
            }
        }
    }

    /**
     * Reset loaded state (untuk testing)
     */
    public static function reset(): void
    {
        static::$isLoaded = false;
        static::$items = [];
        static::$overrides = [];
    }
}

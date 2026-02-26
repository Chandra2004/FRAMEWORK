<?php

namespace TheFramework\App\Cache;

/**
 * CacheManager — Full-Featured Caching System
 * 
 * Mendukung multiple driver (File, Array, Database), tags, atomic locks,
 * increment/decrement, batch operations, garbage collection, dan statistik.
 * 
 * Terinspirasi dari Laravel Cache, ditambah fitur ekstra.
 * 
 * @package TheFramework\App\Cache
 * @version 5.0.1
 */
class CacheManager
{
    /**
     * Driver cache aktif
     */
    protected static string $driver = 'file';

    /**
     * Path folder cache (untuk file driver)
     */
    protected static ?string $cacheDir = null;

    /**
     * In-memory cache store (untuk array driver)
     */
    protected static array $arrayStore = [];

    /**
     * Prefix untuk semua cache keys
     */
    protected static string $prefix = 'tf_';

    /**
     * Statistik hit/miss
     */
    protected static int $hits = 0;
    protected static int $misses = 0;
    protected static int $writes = 0;

    /**
     * Default TTL (detik). 0 = forever
     */
    protected static int $defaultTtl = 3600;

    // ========================================================
    //  CONFIGURATION
    // ========================================================

    /**
     * Set driver cache: 'file', 'array', 'database'
     */
    public static function driver(string $driver): void
    {
        static::$driver = $driver;
    }

    /**
     * Get current driver
     */
    public static function getDriver(): string
    {
        return static::$driver;
    }

    /**
     * Set cache directory (file driver)
     */
    public static function setCacheDir(string $path): void
    {
        static::$cacheDir = rtrim($path, '/\\');
    }

    /**
     * Get cache directory
     */
    public static function getCacheDir(): string
    {
        if (static::$cacheDir === null) {
            $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
            static::$cacheDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        }
        return static::$cacheDir;
    }

    /**
     * Set prefix untuk semua cache keys
     */
    public static function setPrefix(string $prefix): void
    {
        static::$prefix = $prefix;
    }

    /**
     * Set default TTL
     */
    public static function setDefaultTtl(int $seconds): void
    {
        static::$defaultTtl = $seconds;
    }

    // ========================================================
    //  BASIC OPERATIONS
    // ========================================================

    /**
     * Ambil nilai dari cache
     * 
     * @param string $key Cache key
     * @param mixed $default Nilai default jika key tidak ada/expired
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $result = static::retrieve($key);

        if ($result === null) {
            static::$misses++;
            return $default instanceof \Closure ? $default() : $default;
        }

        static::$hits++;
        return $result;
    }

    /**
     * Simpan nilai ke cache
     * 
     * @param string $key Cache key
     * @param mixed $value Nilai (bisa object, array, string, dll)
     * @param int|null $ttl TTL dalam detik. Null = default TTL
     * @return bool
     */
    public static function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? static::$defaultTtl;
        $expiresAt = $ttl > 0 ? time() + $ttl : 0; // 0 = forever

        $result = static::store($key, $value, $expiresAt);
        if ($result)
            static::$writes++;
        return $result;
    }

    /**
     * Simpan hanya jika key BELUM ada
     * 
     * @return bool True jika berhasil disimpan, false jika key sudah ada
     */
    public static function add(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (static::has($key)) {
            return false;
        }
        return static::put($key, $value, $ttl);
    }

    /**
     * Simpan tanpa expiry (forever)
     */
    public static function forever(string $key, mixed $value): bool
    {
        return static::put($key, $value, 0);
    }

    /**
     * Cek apakah cache key ada dan belum expired
     */
    public static function has(string $key): bool
    {
        return static::retrieve($key) !== null;
    }

    /**
     * Cek apakah cache key TIDAK ada
     */
    public static function missing(string $key): bool
    {
        return !static::has($key);
    }

    /**
     * Ambil lalu hapus (get + forget)
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = static::get($key, $default);
        static::forget($key);
        return $value;
    }

    /**
     * Hapus satu atau banyak cache key
     * 
     * @param string|array $keys
     * @return bool
     */
    public static function forget(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : [$keys];
        $success = true;

        foreach ($keys as $key) {
            $success = static::remove($key) && $success;
        }

        return $success;
    }

    /**
     * Hapus SEMUA cache
     * 
     * @return bool
     */
    public static function flush(): bool
    {
        return match (static::$driver) {
            'array' => static::flushArray(),
            'database' => static::flushDatabase(),
            default => static::flushFile(),
        };
    }

    // ========================================================
    //  REMEMBER PATTERN
    // ========================================================

    /**
     * Ambil dari cache, jika tidak ada jalankan callback dan simpan hasilnya
     * 
     * @param string $key Cache key
     * @param int|\Closure|null $ttl TTL dalam detik, atau Closure sebagai callback (legacy support)
     * @param \Closure|null $callback Callback untuk generate value
     * @return mixed
     */
    public static function remember(string $key, int|\Closure|null $ttl = null, ?\Closure $callback = null): mixed
    {
        // Legacy support: remember($key, $ttl, $callback) atau remember($key, $callback)
        if ($ttl instanceof \Closure && $callback === null) {
            $callback = $ttl;
            $ttl = static::$defaultTtl;
        }

        $value = static::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        static::put($key, $value, $ttl);
        return $value;
    }

    /**
     * Remember tanpa expiry
     */
    public static function rememberForever(string $key, \Closure $callback): mixed
    {
        $value = static::get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        static::forever($key, $value);
        return $value;
    }

    /**
     * Refresh: hapus cache lalu jalankan callback dan simpan ulang
     */
    public static function refresh(string $key, \Closure $callback, ?int $ttl = null): mixed
    {
        static::forget($key);
        $value = $callback();
        static::put($key, $value, $ttl);
        return $value;
    }

    // ========================================================
    //  BATCH OPERATIONS
    // ========================================================

    /**
     * Ambil banyak key sekaligus
     * 
     * @param array $keys Array of keys
     * @return array Associative array [key => value] (key yang tidak ada di-skip)
     */
    public static function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $value = static::get($key);
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Simpan banyak key/value sekaligus
     * 
     * @param array $values Associative array [key => value]
     * @param int|null $ttl TTL dalam detik
     * @return bool
     */
    public static function putMany(array $values, ?int $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = static::put($key, $value, $ttl) && $success;
        }
        return $success;
    }

    // ========================================================
    //  COUNTER OPERATIONS
    // ========================================================

    /**
     * Increment nilai numerik
     * 
     * @param string $key
     * @param int $amount Jumlah increment
     * @return int Nilai baru setelah increment
     */
    public static function increment(string $key, int $amount = 1): int
    {
        $current = static::get($key, 0);

        if (!is_numeric($current)) {
            $current = 0;
        }

        $newValue = (int) $current + $amount;
        static::forever($key, $newValue);
        return $newValue;
    }

    /**
     * Decrement nilai numerik
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        return static::increment($key, -$amount);
    }

    // ========================================================
    //  TAGS
    // ========================================================

    /**
     * Cache dengan tags untuk grouped operations
     * 
     * Contoh: Cache::tags(['users', 'active'])->put('user.1', $user, 3600)
     *         Cache::tags(['users'])->flush()  // Hapus semua cache bertag 'users'
     * 
     * @param array $tags
     * @return TaggedCache
     */
    public static function tags(array $tags): TaggedCache
    {
        return new TaggedCache($tags);
    }

    // ========================================================
    //  ATOMIC LOCKS
    // ========================================================

    /**
     * Dapatkan atomic lock
     * 
     * @param string $name Nama lock
     * @param int $seconds Durasi lock (0 = manual release)
     * @return CacheLock
     */
    public static function lock(string $name, int $seconds = 0): CacheLock
    {
        return new CacheLock($name, $seconds);
    }

    // ========================================================
    //  GARBAGE COLLECTION
    // ========================================================

    /**
     * Hapus semua cache file yang sudah expired (file driver only)
     * 
     * @return int Jumlah file yang dihapus
     */
    public static function gc(): int
    {
        if (static::$driver !== 'file')
            return 0;

        $dir = static::getCacheDir();
        if (!is_dir($dir))
            return 0;

        $deleted = 0;
        $now = time();

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') as $file) {
            $content = @file_get_contents($file);
            if ($content === false)
                continue;

            $data = @unserialize($content);
            if ($data === false)
                $data = @json_decode($content, true); // Legacy JSON support

            if (is_array($data) && isset($data['expires_at']) && $data['expires_at'] > 0 && $data['expires_at'] < $now) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Probabilistic GC — jalankan gc() secara random (1 dari N request)
     */
    public static function gcProbabilistic(int $probability = 100): void
    {
        if (random_int(1, $probability) === 1) {
            static::gc();
        }
    }

    // ========================================================
    //  STATISTICS & INFO
    // ========================================================

    /**
     * Dapatkan statistik cache
     */
    public static function stats(): array
    {
        $total = static::$hits + static::$misses;
        $hitRate = $total > 0 ? round((static::$hits / $total) * 100, 1) : 0;

        return [
            'driver' => static::$driver,
            'hits' => static::$hits,
            'misses' => static::$misses,
            'writes' => static::$writes,
            'hit_rate' => $hitRate . '%',
            'total' => $total,
        ];
    }

    /**
     * Reset statistik
     */
    public static function resetStats(): void
    {
        static::$hits = 0;
        static::$misses = 0;
        static::$writes = 0;
    }

    /**
     * Hitung jumlah item di cache (file driver)
     */
    public static function count(): int
    {
        return match (static::$driver) {
            'array' => count(static::$arrayStore),
            'database' => static::countDatabase(),
            default => static::countFile(),
        };
    }

    /**
     * Dapatkan ukuran total cache dalam bytes (file driver)
     */
    public static function size(): int
    {
        if (static::$driver !== 'file')
            return 0;

        $dir = static::getCacheDir();
        if (!is_dir($dir))
            return 0;

        $size = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') as $file) {
            $size += filesize($file);
        }
        return $size;
    }

    /**
     * Formatted summary
     */
    public static function summary(): string
    {
        $stats = static::stats();
        $count = static::count();
        $sizeBytes = static::size();
        $sizeFormatted = static::formatBytes($sizeBytes);

        $lines = [];
        $lines[] = "📊 Cache Summary ({$stats['driver']} driver)";
        $lines[] = str_repeat('─', 40);
        $lines[] = "   Items:    $count";
        $lines[] = "   Size:     $sizeFormatted";
        $lines[] = "   Hits:     {$stats['hits']}";
        $lines[] = "   Misses:   {$stats['misses']}";
        $lines[] = "   Writes:   {$stats['writes']}";
        $lines[] = "   Hit Rate: {$stats['hit_rate']}";

        return implode("\n", $lines);
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)
            return $bytes . 'B';
        if ($bytes < 1048576)
            return round($bytes / 1024, 1) . 'KB';
        return round($bytes / 1048576, 1) . 'MB';
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Rate limiter menggunakan cache
     * 
     * @param string $key Identifier (misal: "api:$ip")
     * @param int $maxAttempts Max percobaan
     * @param int $decaySeconds Window waktu (detik)
     * @return bool True jika masih diizinkan, false jika rate limit exceeded
     */
    public static function rateLimit(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $cacheKey = 'rate_limit:' . $key;
        $current = static::get($cacheKey, 0);

        if ($current >= $maxAttempts) {
            return false;
        }

        if ($current === 0) {
            static::put($cacheKey, 1, $decaySeconds);
        } else {
            static::increment($cacheKey);
        }

        return true;
    }

    /**
     * Dapatkan sisa attempts untuk rate limiter
     */
    public static function rateLimitRemaining(string $key, int $maxAttempts): int
    {
        $current = static::get('rate_limit:' . $key, 0);
        return max(0, $maxAttempts - (int) $current);
    }

    /**
     * Cache dengan fallback cascade
     * Coba key pertama, jika tidak ada coba key kedua, dst.
     * 
     * @param array $keys Array of keys (prioritas tinggi → rendah)
     * @param mixed $default Default jika semua miss
     * @return mixed
     */
    public static function cascade(array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = static::get($key);
            if ($value !== null)
                return $value;
        }
        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * Cache dengan swr (Stale-While-Revalidate) pattern
     * Return stale data langsung, refresh di background
     * 
     * @param string $key
     * @param int $ttl TTL normal
     * @param int $staleTtl Berapa lama data stale masih bisa dipakai
     * @param \Closure $callback
     * @return mixed
     */
    public static function swr(string $key, int $ttl, int $staleTtl, \Closure $callback): mixed
    {
        $data = static::retrieveRaw($key);

        if ($data !== null) {
            $now = time();
            $expiresAt = $data['expires_at'] ?? 0;

            // Masih fresh
            if ($expiresAt === 0 || $now < $expiresAt) {
                return $data['value'];
            }

            // Stale tapi masih dalam window
            if ($now < $expiresAt + $staleTtl) {
                // Return stale & refresh
                $value = $callback();
                static::put($key, $value, $ttl);
                return $data['value']; // Return stale value (cepat)
            }
        }

        // Miss atau terlalu stale
        $value = $callback();
        static::put($key, $value, $ttl);
        return $value;
    }

    /**
     * List semua cache keys (file driver, untuk admin/debugging)
     * 
     * @return array
     */
    public static function keys(): array
    {
        if (static::$driver === 'array') {
            return array_keys(static::$arrayStore);
        }

        if (static::$driver !== 'file')
            return [];

        $dir = static::getCacheDir();
        if (!is_dir($dir))
            return [];

        $keys = [];
        $now = time();

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') as $file) {
            $content = @file_get_contents($file);
            if ($content === false)
                continue;

            $data = @unserialize($content);
            if ($data === false)
                $data = @json_decode($content, true);

            if (is_array($data)) {
                $expiresAt = $data['expires_at'] ?? 0;
                if ($expiresAt === 0 || $now < $expiresAt) {
                    $keys[] = [
                        'key' => $data['original_key'] ?? basename($file, '.cache'),
                        'expires_at' => $expiresAt > 0 ? date('Y-m-d H:i:s', $expiresAt) : 'forever',
                        'size' => filesize($file),
                    ];
                }
            }
        }

        return $keys;
    }

    // ========================================================
    //  DRIVER: FILE
    // ========================================================

    protected static function ensureCacheDir(): void
    {
        $dir = static::getCacheDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected static function cacheFilePath(string $key): string
    {
        static::ensureCacheDir();
        $hash = hash('sha256', static::$prefix . $key);
        return static::getCacheDir() . DIRECTORY_SEPARATOR . $hash . '.cache';
    }

    // ========================================================
    //  DRIVER DISPATCH — Internal Methods
    // ========================================================

    protected static function retrieve(string $key): mixed
    {
        return match (static::$driver) {
            'array' => static::retrieveFromArray($key),
            'database' => static::retrieveFromDatabase($key),
            default => static::retrieveFromFile($key),
        };
    }

    protected static function retrieveRaw(string $key): ?array
    {
        if (static::$driver === 'file') {
            $filePath = static::cacheFilePath($key);
            if (!file_exists($filePath))
                return null;
            $content = @file_get_contents($filePath);
            if ($content === false)
                return null;
            $data = @unserialize($content);
            if ($data === false)
                $data = @json_decode($content, true);
            return is_array($data) ? $data : null;
        }
        $value = static::retrieve($key);
        return $value !== null ? ['value' => $value, 'expires_at' => 0] : null;
    }

    protected static function store(string $key, mixed $value, int $expiresAt): bool
    {
        return match (static::$driver) {
            'array' => static::storeToArray($key, $value, $expiresAt),
            'database' => static::storeToDatabase($key, $value, $expiresAt),
            default => static::storeToFile($key, $value, $expiresAt),
        };
    }

    protected static function remove(string $key): bool
    {
        return match (static::$driver) {
            'array' => static::removeFromArray($key),
            'database' => static::removeFromDatabase($key),
            default => static::removeFromFile($key),
        };
    }

    // ========================================================
    //  FILE DRIVER IMPLEMENTATION
    // ========================================================

    protected static function retrieveFromFile(string $key): mixed
    {
        $filePath = static::cacheFilePath($key);

        if (!file_exists($filePath))
            return null;

        $content = @file_get_contents($filePath);
        if ($content === false)
            return null;

        $data = @unserialize($content);
        if ($data === false) {
            $data = @json_decode($content, true); // Legacy JSON support
        }

        if (!is_array($data) || !array_key_exists('value', $data))
            return null;

        // Check expiry (0 = forever)
        if (isset($data['expires_at']) && $data['expires_at'] > 0 && time() >= $data['expires_at']) {
            @unlink($filePath); // Lazy deletion
            return null;
        }

        return $data['value'];
    }

    protected static function storeToFile(string $key, mixed $value, int $expiresAt): bool
    {
        $filePath = static::cacheFilePath($key);

        $data = [
            'original_key' => $key,
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];

        $result = @file_put_contents($filePath, serialize($data), LOCK_EX);
        return $result !== false;
    }

    protected static function removeFromFile(string $key): bool
    {
        $filePath = static::cacheFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    protected static function flushFile(): bool
    {
        $dir = static::getCacheDir();
        if (!is_dir($dir))
            return true;

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    protected static function countFile(): int
    {
        $dir = static::getCacheDir();
        if (!is_dir($dir))
            return 0;

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.cache');
        return count($files);
    }

    // ========================================================
    //  ARRAY DRIVER IMPLEMENTATION (In-Memory, untuk Testing)
    // ========================================================

    protected static function retrieveFromArray(string $key): mixed
    {
        $prefixed = static::$prefix . $key;
        if (!isset(static::$arrayStore[$prefixed]))
            return null;

        $data = static::$arrayStore[$prefixed];
        if ($data['expires_at'] > 0 && time() >= $data['expires_at']) {
            unset(static::$arrayStore[$prefixed]);
            return null;
        }

        return $data['value'];
    }

    protected static function storeToArray(string $key, mixed $value, int $expiresAt): bool
    {
        static::$arrayStore[static::$prefix . $key] = [
            'value' => $value,
            'expires_at' => $expiresAt,
        ];
        return true;
    }

    protected static function removeFromArray(string $key): bool
    {
        unset(static::$arrayStore[static::$prefix . $key]);
        return true;
    }

    protected static function flushArray(): bool
    {
        static::$arrayStore = [];
        return true;
    }

    // ========================================================
    //  DATABASE DRIVER IMPLEMENTATION
    // ========================================================

    protected static function getDb(): \TheFramework\App\Database\Database
    {
        return \TheFramework\App\Database\Database::getInstance();
    }

    /**
     * Auto-create cache table
     */
    public static function createCacheTable(): void
    {
        $db = static::getDb();
        $sql = "CREATE TABLE IF NOT EXISTS `cache` (
            `key` VARCHAR(255) PRIMARY KEY,
            `value` LONGTEXT NOT NULL,
            `expires_at` INT UNSIGNED NOT NULL DEFAULT 0,
            INDEX `idx_cache_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $db->query($sql);
        $db->execute();
    }

    protected static function retrieveFromDatabase(string $key): mixed
    {
        try {
            $db = static::getDb();
            $prefixed = static::$prefix . $key;

            $db->query("SELECT `value`, `expires_at` FROM `cache` WHERE `key` = :key;");
            $db->bind(':key', $prefixed);
            $row = $db->single();

            if (!$row)
                return null;

            if ($row['expires_at'] > 0 && time() >= (int) $row['expires_at']) {
                static::removeFromDatabase($key);
                return null;
            }

            return unserialize($row['value']);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function storeToDatabase(string $key, mixed $value, int $expiresAt): bool
    {
        try {
            $db = static::getDb();
            $prefixed = static::$prefix . $key;
            $serialized = serialize($value);

            $sql = "INSERT INTO `cache` (`key`, `value`, `expires_at`) VALUES (:key, :value, :expires)
                    ON DUPLICATE KEY UPDATE `value` = :value2, `expires_at` = :expires2;";
            $db->query($sql);
            $db->bind(':key', $prefixed);
            $db->bind(':value', $serialized);
            $db->bind(':expires', $expiresAt);
            $db->bind(':value2', $serialized);
            $db->bind(':expires2', $expiresAt);
            $db->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function removeFromDatabase(string $key): bool
    {
        try {
            $db = static::getDb();
            $db->query("DELETE FROM `cache` WHERE `key` = :key;");
            $db->bind(':key', static::$prefix . $key);
            $db->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function flushDatabase(): bool
    {
        try {
            $db = static::getDb();
            $db->query("TRUNCATE TABLE `cache`;");
            $db->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function countDatabase(): int
    {
        try {
            $db = static::getDb();
            $now = time();
            $db->query("SELECT COUNT(*) AS cnt FROM `cache` WHERE `expires_at` = 0 OR `expires_at` > :now;");
            $db->bind(':now', $now);
            $row = $db->single();
            return (int) ($row['cnt'] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}

// ========================================================
//  TAGGED CACHE
// ========================================================

/**
 * TaggedCache — Cache operations scoped to tags
 */
class TaggedCache
{
    private array $tags;

    public function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    private function taggedKey(string $key): string
    {
        $tagPrefix = 'tag:' . implode(':', $this->tags) . ':';
        return $tagPrefix . $key;
    }

    private function registerTag(string $key): void
    {
        foreach ($this->tags as $tag) {
            $tagSetKey = '_tag_set:' . $tag;
            $existing = CacheManager::get($tagSetKey, []);
            if (!in_array($key, $existing)) {
                $existing[] = $key;
                CacheManager::forever($tagSetKey, $existing);
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return CacheManager::get($this->taggedKey($key), $default);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $taggedKey = $this->taggedKey($key);
        $this->registerTag($taggedKey);
        return CacheManager::put($taggedKey, $value, $ttl);
    }

    public function forever(string $key, mixed $value): bool
    {
        $taggedKey = $this->taggedKey($key);
        $this->registerTag($taggedKey);
        return CacheManager::forever($taggedKey, $value);
    }

    public function forget(string $key): bool
    {
        return CacheManager::forget($this->taggedKey($key));
    }

    public function has(string $key): bool
    {
        return CacheManager::has($this->taggedKey($key));
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        $taggedKey = $this->taggedKey($key);
        $this->registerTag($taggedKey);
        return CacheManager::remember($taggedKey, $ttl, $callback);
    }

    /**
     * Flush semua cache yang memiliki tag ini
     */
    public function flush(): bool
    {
        $success = true;
        foreach ($this->tags as $tag) {
            $tagSetKey = '_tag_set:' . $tag;
            $keys = CacheManager::get($tagSetKey, []);

            foreach ($keys as $key) {
                $success = CacheManager::forget($key) && $success;
            }

            CacheManager::forget($tagSetKey);
        }
        return $success;
    }
}

// ========================================================
//  ATOMIC LOCK
// ========================================================

/**
 * CacheLock — Atomic locking via cache
 */
class CacheLock
{
    private string $name;
    private int $seconds;
    private ?string $owner;

    public function __construct(string $name, int $seconds = 0)
    {
        $this->name = '_lock:' . $name;
        $this->seconds = $seconds;
        $this->owner = bin2hex(random_bytes(8));
    }

    /**
     * Coba acquire lock
     * 
     * @param \Closure|null $callback Jalankan jika berhasil dapat lock
     * @return bool|mixed
     */
    public function get(?\Closure $callback = null): mixed
    {
        $acquired = CacheManager::add($this->name, $this->owner, $this->seconds ?: 86400);

        if (!$acquired) {
            return false;
        }

        if ($callback) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    /**
     * Tunggu hingga lock tersedia, lalu acquire
     * 
     * @param int $waitSeconds Max waktu tunggu
     * @param \Closure|null $callback
     * @return mixed
     */
    public function block(int $waitSeconds, ?\Closure $callback = null): mixed
    {
        $start = time();

        while (!$this->get()) {
            if ((time() - $start) >= $waitSeconds) {
                throw new \RuntimeException("Lock [{$this->name}] tidak tersedia setelah {$waitSeconds}s");
            }
            usleep(250000); // 250ms
        }

        if ($callback) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    /**
     * Release lock (hanya jika owner cocok)
     */
    public function release(): bool
    {
        $current = CacheManager::get($this->name);
        if ($current === $this->owner) {
            return CacheManager::forget($this->name);
        }
        return false;
    }

    /**
     * Force release (abaikan ownership)
     */
    public function forceRelease(): bool
    {
        return CacheManager::forget($this->name);
    }

    /**
     * Cek apakah lock sedang aktif
     */
    public function isLocked(): bool
    {
        return CacheManager::has($this->name);
    }

    /**
     * Get current owner
     */
    public function getOwner(): ?string
    {
        return CacheManager::get($this->name);
    }
}

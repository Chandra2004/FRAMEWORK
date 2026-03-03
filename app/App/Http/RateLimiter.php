<?php

namespace TheFramework\App\Http;

use TheFramework\App\Core\Logging;
use TheFramework\App\Core\Config;

/**
 * RateLimiter — Throttling Engine (v5.0.1)
 * 
 * Membatasi jumlah request berdasarkan key (IP/User ID/Action).
 * Mendukung status bypass di lingkungan pengembangan (local/debug).
 */
class RateLimiter
{
    private static ?string $cacheDir = null;

    /**
     * Get rate limit cache directory
     */
    private static function getCacheDir(): string
    {
        if (self::$cacheDir === null) {
            $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2));
            self::$cacheDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'ratelimit' . DIRECTORY_SEPARATOR;
        }
        return self::$cacheDir;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param string $key
     * @param int $decay Seconds
     * @return int New count
     */
    public static function hit(string $key, int $decay = 60): int
    {
        $dir = self::getCacheDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . md5($key) . '.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [
            'count' => 0,
            'timestamp' => time()
        ];

        // Reset if window expired
        if (time() - $data['timestamp'] > $decay) {
            $data = ['count' => 0, 'timestamp' => time()];
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        return $data['count'];
    }

    /**
     * Determine if the given key has been "too many attempts".
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decay Seconds
     * @return bool
     */
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decay = 60): bool
    {
        // Bypass rate limiting in development
        if (Config::get('APP_ENV') === 'local' || Config::get('APP_DEBUG') === 'true') {
            return false;
        }

        $dir = self::getCacheDir();
        $file = $dir . md5($key) . '.json';

        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode(file_get_contents($file), true);

        // Reset if window expired
        if (time() - $data['timestamp'] > $decay) {
            return false;
        }

        return $data['count'] >= $maxAttempts;
    }

    /**
     * Clear the attempts for the given key.
     *
     * @param string $key
     */
    public static function clear(string $key): void
    {
        $dir = self::getCacheDir();
        $file = $dir . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get the number of seconds until the "key" is available again.
     *
     * @param string $key
     * @return int
     */
    public static function availableIn(string $key): int
    {
        $dir = self::getCacheDir();
        $file = $dir . md5($key) . '.json';

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);

        // Find decay from hit? No, but we can assume default 60 or 
        // better yet, we just give the remaining time from the timestamp recorded.
        // Usually, decay is provided in tooManyAttempts.
        // We'll use 60 as default or store decay in data.
        $decay = $data['decay'] ?? 60;

        $diff = time() - $data['timestamp'];
        return max(0, $decay - $diff);
    }

    /**
     * Get the number of remaining attempts for a given key.
     *
     * @param string $key
     * @param int $maxAttempts
     * @return int
     */
    public static function remaining(string $key, int $maxAttempts): int
    {
        $dir = self::getCacheDir();
        $file = $dir . md5($key) . '.json';

        if (!file_exists($file)) {
            return $maxAttempts;
        }

        $data = json_decode(file_get_contents($file), true);
        return max(0, $maxAttempts - $data['count']);
    }

    /**
     * Backward compatibility check method (Deprecated style)
     * Throws exception on failure.
     */
    public static function check($key, $limit = 100, $window = 60)
    {
        if (self::tooManyAttempts($key, $limit, $window)) {
            self::sendRateLimitResponse($window);
        }

        self::hit($key, $window);
        return true;
    }

    /**
     * Response status 429
     */
    private static function sendRateLimitResponse($window)
    {
        throw new \TheFramework\App\Exceptions\TooManyRequestsHttpException($window, 'Too Many Requests');
    }
}

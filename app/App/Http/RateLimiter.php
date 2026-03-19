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
    private static string $prefix = 'ratelimit:';

    /**
     * Get unique cache key
     */
    private static function resolveKey(string $key): string
    {
        return self::$prefix . md5($key);
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
        $cacheKey = self::resolveKey($key);
        $lockKey = 'lock:' . $cacheKey;

        return \TheFramework\App\Cache\CacheManager::lock($lockKey, 2)->get(function () use ($cacheKey, $decay) {
            $data = \TheFramework\App\Cache\CacheManager::get($cacheKey);
            
            if (!$data || time() - $data['timestamp'] > $decay) {
                $data = [
                    'count' => 0,
                    'timestamp' => time(),
                    'decay' => $decay
                ];
            }

            $data['count']++;
            $data['decay'] = $decay;
            
            \TheFramework\App\Cache\CacheManager::put($cacheKey, $data, $decay);
            
            return $data['count'];
        }) ?? 1;
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

        $cacheKey = self::resolveKey($key);
        $data = \TheFramework\App\Cache\CacheManager::get($cacheKey);

        if (!$data) {
            return false;
        }

        // Reset if window expired (Double-check TTL)
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
        \TheFramework\App\Cache\CacheManager::forget(self::resolveKey($key));
    }

    /**
     * Get the number of seconds until the "key" is available again.
     *
     * @param string $key
     * @return int
     */
    public static function availableIn(string $key): int
    {
        $cacheKey = self::resolveKey($key);
        $data = \TheFramework\App\Cache\CacheManager::get($cacheKey);

        if (!$data) {
            return 0;
        }

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
        $cacheKey = self::resolveKey($key);
        $data = \TheFramework\App\Cache\CacheManager::get($cacheKey);

        if (!$data) {
            return $maxAttempts;
        }

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

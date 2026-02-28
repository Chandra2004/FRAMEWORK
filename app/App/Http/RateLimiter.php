<?php

namespace TheFramework\App\Http;

use TheFramework\App\Core\Logging;
use TheFramework\App\Core\Config;

class RateLimiter
{
    private static ?string $fallbackDir = null;
    private static $fallbackLimit = 50; // Limit fallback lebih ketat
    private static $window = 60; // Waktu window dalam detik

    /**
     * Get rate limit cache directory (consistent with CacheManager path resolution)
     */
    private static function getFallbackDir(): string
    {
        if (self::$fallbackDir === null) {
            $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
            self::$fallbackDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'ratelimit' . DIRECTORY_SEPARATOR;
        }
        return self::$fallbackDir;
    }

    /**
     * Mengecek apakah permintaan bisa diproses berdasarkan rate limiting.
     *
     * @param string $key Kunci untuk rate limiting (misalnya IP address atau user ID)
     * @param int $limit Jumlah permintaan maksimal dalam window waktu
     * @param int $window Waktu dalam detik untuk jendela rate limiting
     * @return bool True jika permintaan diizinkan, False jika terlalu banyak permintaan
     */
    public static function check($key, $limit = 100, $window = 60)
    {
        // Bypass rate limiting in development
        if (Config::get('APP_ENV') === 'local' || Config::get('APP_DEBUG') === 'true') {
            return true;
        }

        try {
            return self::fallbackCheck($key, $limit, $window);
        } catch (\Exception $e) {
            Logging::getLogger()->error('Rate Limiter Error: ' . $e->getMessage());
            return self::fallbackCheck($key, $limit, $window);
        }
    }

    /**
     * Fallback untuk mengecek rate limit menggunakan file cache.
     *
     * @param string $key Kunci untuk rate limiting
     * @param int $limit Batas permintaan
     * @param int $window Jendela waktu dalam detik
     * @return bool True jika permintaan diizinkan, False jika rate limit tercapai
     */
    private static function fallbackCheck($key, $limit, $window)
    {
        $dir = self::getFallbackDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $dir . md5($key) . '.json';
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [
            'count' => 0,
            'timestamp' => time()
        ];

        if (time() - $data['timestamp'] > $window) {
            $data = ['count' => 0, 'timestamp' => time()];
        }

        if ($data['count'] >= $limit) {
            self::sendRateLimitResponse($window);
            return false;
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }

    /**
     * Kirimkan response dengan status 429 jika rate limit tercapai
     */
    private static function sendRateLimitResponse($window)
    {
        throw new \TheFramework\App\Exceptions\TooManyRequestsHttpException($window, 'Too Many Requests');
    }
}

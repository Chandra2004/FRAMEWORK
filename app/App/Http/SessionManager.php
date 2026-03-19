<?php

namespace TheFramework\App\Http;

use TheFramework\App\Core\Config;

class SessionManager
{
    private static bool $started = false;

    public static function startSecureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', $isSecure ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');

            $driver = Config::get('SESSION_DRIVER', 'file');

            if ($driver === 'redis' && extension_loaded('redis')) {
                $host = Config::get('REDIS_HOST', '127.0.0.1');
                $port = Config::get('REDIS_PORT', 6379);
                $pass = Config::get('REDIS_PASSWORD');
                $prefix = Config::get('REDIS_PREFIX', 'tf_sess:');
                
                $savePath = "tcp://{$host}:{$port}?prefix={$prefix}";
                if ($pass) {
                    $savePath .= "&auth=" . urlencode($pass);
                }

                ini_set('session.save_handler', 'redis');
                ini_set('session.save_path', $savePath);
            } else {
                // File-based session fallback
                $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
                $sessionPath = $root . '/storage/session';

                if (!is_dir($sessionPath)) {
                    @mkdir($sessionPath, 0777, true);
                }
                if (is_writable($sessionPath)) {
                    session_save_path($sessionPath);
                }
            }

            session_start();
            self::$started = true;

            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }

            $timeout = (int) Config::get('SESSION_LIFETIME', 120) * 60;
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                self::destroySession();
                session_start();
                session_regenerate_id(true);
            }
            $_SESSION['last_activity'] = time();

            self::ageFlashData();
        }
    }

    public static function regenerateSession()
    {
        session_regenerate_id(true);
    }

    public static function destroySession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');
        }
        self::$started = false;
    }

    // ========================================================
    //  SESSION DATA MANIPULATION
    // ========================================================

    public static function put(string $key, $value): void
    {
        self::ensureSession();
        $_SESSION['data'][$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        self::ensureSession();
        return $_SESSION['data'][$key] ?? $default;
    }

    public static function all(): array
    {
        self::ensureSession();
        return $_SESSION['data'] ?? [];
    }

    public static function has(string $key): bool
    {
        self::ensureSession();
        return isset($_SESSION['data'][$key]);
    }

    public static function forget(string $key): void
    {
        self::ensureSession();
        unset($_SESSION['data'][$key]);
    }

    public static function pull(string $key, $default = null)
    {
        $value = self::get($key, $default);
        self::forget($key);
        return $value;
    }

    public static function flush(): void
    {
        self::ensureSession();
        $_SESSION['data'] = [];
        $_SESSION['flash'] = [];
    }

    // ========================================================
    //  FLASH DATA
    // ========================================================

    public static function flash(string $key, $value): void
    {
        self::ensureSession();
        $_SESSION['flash']['new'][] = $key;
        $_SESSION['data'][$key] = $value;
    }

    public static function reflash(): void
    {
        self::ensureSession();
        $old = $_SESSION['flash']['old'] ?? [];
        $_SESSION['flash']['new'] = array_merge($_SESSION['flash']['new'] ?? [], $old);
        $_SESSION['flash']['old'] = [];
    }

    public static function keep($keys): void
    {
        self::ensureSession();
        $keys = is_array($keys) ? $keys : func_get_args();
        $old = $_SESSION['flash']['old'] ?? [];

        foreach ($keys as $key) {
            if (in_array($key, $old)) {
                $_SESSION['flash']['new'][] = $key;
                $old = array_diff($old, [$key]);
            }
        }

        $_SESSION['flash']['old'] = $old;
    }

    private static function ageFlashData(): void
    {
        self::ensureSession();

        $old = $_SESSION['flash']['old'] ?? [];
        foreach ($old as $key) {
            self::forget($key);
        }

        $_SESSION['flash']['old'] = $_SESSION['flash']['new'] ?? [];
        $_SESSION['flash']['new'] = [];
    }

    private static function ensureSession(): void
    {
        if (!self::$started) {
            self::startSecureSession();
        }
        if (!isset($_SESSION['data'])) {
            $_SESSION['data'] = [];
        }
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = ['old' => [], 'new' => []];
        }
    }
}

<?php

namespace TheFramework\App;

use TheFramework\App\Config;
use TheFramework\Helpers\Helper;

class SessionManager
{
    public static function startSecureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {

            // Deteksi apakah berjalan di HTTPS
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', $isSecure ? 1 : 0);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');

            // Set custom session save path untuk InfinityFree compatibility
            $sessionPath = defined('ROOT_DIR') ? ROOT_DIR . '/storage/session' : dirname(__DIR__, 2) . '/storage/session';
            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0777, true);
            }
            if (is_writable($sessionPath)) {
                session_save_path($sessionPath);
            }

            session_start();

            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }

            // 🔒 SECURITY NOTE: User Agent checking disabled (too strict)
            // Problem: Browser updates → user agent changes → legitimate users kicked out
            // Laravel, Symfony, and other frameworks don't implement this check
            // If enabled, should be configurable via SESSION_CHECK_USER_AGENT=true/.env
            /*
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (!isset($_SESSION['user_agent'])) {
                $_SESSION['user_agent'] = $userAgent;
            } elseif ($_SESSION['user_agent'] !== $userAgent) {
                self::destroySession();
                return Helper::redirect('/login', 'warning', 'Invalid session');
            }
            */

            $timeout = 30 * 60;
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                self::destroySession();
                session_start();
                session_regenerate_id(true);
            }
            $_SESSION['last_activity'] = time();
        }
    }

    public static function regenerateSession()
    {
        session_regenerate_id(true);
    }

    public static function destroySession()
    {
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

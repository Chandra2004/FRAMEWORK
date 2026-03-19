<?php

namespace TheFramework\Helpers;

use DateTime;
use DateTimeZone;
use TheFramework\App\Core\Config;
use TheFramework\App\Database\Database;
use TheFramework\App\Http\SessionManager;

/**
 * Main Helper Class - The Framework v5.0
 * Pintu masuk utama untuk semua utilitas framework.
 */
class Helper
{
    /**
     * Session Ensure - Paten secure session.
     */
    private static function ensureSession()
    {
        SessionManager::startSecureSession();
    }

    /**
     * Generate URL aplikasi.
     */
    public static function url(string $path = ''): string
    {
        if (preg_match('#^https?://#', $path))
            return $path;
        $baseUrl = Config::get('BASE_URL') ?: '/';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Redirect dengan Flash Message premium.
     */
    public static function redirect(string $url, ?string $status = null, ?string $message = null, int $duration = 5): void
    {
        if ($status && $message) {
            $flashData = [
                'redirect' => $url,
                'status' => $status,
                'message' => $message,
                'expires_at' => time() + $duration,
                'duration' => $duration * 1000
            ];
            self::set_flash('notification', $flashData);
        }

        if (self::is_ajax()) {
            self::json_redirect($url);
        } else {
            header("Location: " . self::url($url));
            if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
                throw new \Exception('REDIRECT:' . self::url($url), 302);
            }
            exit();
        }
    }

    /**
     * Manipulasi Request (Wrapper).
     *
     * @param string|null $key
     * @param mixed $default
     * @return \TheFramework\App\Http\Request|mixed
     */
    public static function request($key = null, $default = null)
    {
        $request = \TheFramework\App\Core\Container::getInstance()->make(\TheFramework\App\Http\Request::class);

        if ($key !== null) {
            return $request->input($key, $default);
        }

        return $request;
    }

    /**
     * Flash Messages.
     */
    public static function set_flash(string $key, $value): void
    {
        self::ensureSession();
        $_SESSION['flash_system'][$key] = $value;
    }

    public static function get_flash(string $key)
    {
        self::ensureSession();

        $value = null;
        if (isset($_SESSION['flash_system'][$key])) {
            $value = $_SESSION['flash_system'][$key];
            unset($_SESSION['flash_system'][$key]);
        } elseif (isset($_SESSION[$key])) { // Fallback for root session items like 'notification'
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }

        if (is_array($value) && isset($value['expires_at'])) {
            if (time() > $value['expires_at']) {
                return null; // Expired
            }
        }

        return $value;
    }

    /**
     * Validation & Old Input.
     */
    public static function old(?string $field = null, $default = null)
    {
        self::ensureSession();
        $oldInput = $_SESSION['old_input'] ?? $_SESSION['_old_input'] ?? [];
        if ($field === null) {
            return $oldInput;
        }
        return $oldInput[$field] ?? $default;
    }

    public static function validation_errors(?string $field = null)
    {
        self::ensureSession();
        $errors = $_SESSION['validation_errors'] ?? $_SESSION['_errors'] ?? [];
        return $field === null ? $errors : ($errors[$field] ?? null);
    }

    public static function has_error(string $field): bool
    {
        return self::validation_errors($field) !== null;
    }

    /**
     * Formatting Utillities.
     */
    public static function rupiah($angka): string
    {
        return "Rp " . number_format((float) $angka, 0, ',', '.');
    }

    public static function e($string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Security.
     */
    public static function hash_password(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Networking.
     */
    public static function get_client_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Trusted Proxies support
        $trustedProxies = Config::get('app.trusted_proxies', []);
        
        if (!empty($trustedProxies)) {
            $isTrusted = false;
            foreach ((array)$trustedProxies as $proxy) {
                if ($proxy === '*' || $proxy === $ip) {
                    $isTrusted = true;
                    break;
                }
            }

            if ($isTrusted && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim(end($ips));
            }
        }

        return $ip;
    }

    public static function is_ajax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * JSON Response.
     */
    public static function json($data = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode($data);
        
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
            throw new \Exception('JSON_RESPONSE:' . json_encode($data), $statusCode);
        }
        exit();
    }

    /**
     * JSON Redirect for AJAX requests.
     */
    public static function json_redirect(string $url): void
    {
        $data = ['redirect' => self::url($url)];
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode($data);

        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
            throw new \Exception('JSON_REDIRECT:' . self::url($url), 302);
        }
        exit();
    }

    /**
     * Session Helpers.
     */
    public static function session_get(string $key, $default = null)
    {
        self::ensureSession();
        return $_SESSION[$key] ?? $default;
    }

    public static function session_write(string $key, $value): void
    {
        self::ensureSession();
        $_SESSION[$key] = $value;
    }

    public static function session_destroy_all(): void
    {
        self::ensureSession();
        session_unset();
        session_destroy();
    }

    public static function session_has(string $key): bool
    {
        self::ensureSession();
        return isset($_SESSION[$key]);
    }

    public static function session_pull(string $key, $default = null)
    {
        $value = self::session_get($key, $default);
        unset($_SESSION[$key]);
        return $value;
    }

    /**
     * Auth Token Helpers.
     */
    public static function generateAuthToken(string $uid): string
    {
        return bin2hex(random_bytes(32)) . '.' . hash('sha256', $uid . time());
    }

    public static function getAuthToken(): ?string
    {
        return self::session_get('auth_token');
    }

    public static function validateAuthToken(string $token, string $uid): bool
    {
        $sessionToken = self::session_get('auth_token');
        $sessionUid = self::session_get('user.uid');

        if (!$sessionToken || !$sessionUid) {
            return false;
        }

        // Validasi token DAN kepemilikan UID agar tidak terjadi session hijacking lintas user
        return hash_equals($sessionToken, $token) && hash_equals((string)$sessionUid, (string)$uid);
    }

    /**
     * Security & CSRF.
     */
    public static function generateCsrfToken(): string
    {
        self::ensureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        self::ensureSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Slug Generation (Proxy to Str).
     */
    public static function slug(string $text): string
    {
        return Str::slug($text);
    }

    /**
     * Alias for slug() to match documentation.
     */
    public static function slugify(string $text): string
    {
        return self::slug($text);
    }
}

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
            exit();
        }
    }

    /**
     * Manipulasi Request (Wrapper).
     */
    public static function request($key = null, $default = null)
    {
        $data = array_merge($_GET, $_POST);
        if ($key !== null)
            return $data[$key] ?? $default;

        return new class ($data) {
            private $data;
            public function __construct($data)
            {
                $this->data = $data; }
            public function all()
            {
                return $this->data; }
            public function only(array $keys)
            {
                return array_intersect_key($this->data, array_flip($keys)); }
            public function except(array $keys)
            {
                return array_diff_key($this->data, array_flip($keys)); }
            public function get($key, $default = null)
            {
                return $this->data[$key] ?? $default; }
            public function has($key)
            {
                return isset($this->data[$key]); }
            public function path()
            {
                return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); }
            public function ip()
            {
                return Helper::get_client_ip(); }
            public function method()
            {
                return strtoupper($_SERVER['REQUEST_METHOD']); }
            public function isMethod($method)
            {
                return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method); }
        };
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
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return filter_var(trim($ips[0]), FILTER_VALIDATE_IP) ?: '0.0.0.0';
            }
        }
        return '0.0.0.0';
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
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * JSON Redirect for AJAX requests.
     */
    public static function json_redirect(string $url): void
    {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => self::url($url)]);
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
    public static function getAuthToken(): ?string
    {
        return self::session_get('auth_token');
    }

    public static function validateAuthToken(string $token, string $uid): bool
    {
        $sessionToken = self::session_get('auth_token');
        if (!$sessionToken)
            return false;

        // Sederhana: Token harus sama dengan di session
        // Kita bisa tambahkan pengecekan fingerprint IP/User-Agent disini agar LEBIH AMAN.
        return hash_equals($sessionToken, $token);
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
}

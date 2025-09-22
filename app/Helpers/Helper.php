<?php

namespace TheFramework\Helpers;

use DateTime;
use DateTimeZone;
use TheFramework\App\Config;
use TheFramework\App\Database;

class Helper
{
    private static function ensureSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function url($path = '')
    {
        $baseUrl = Config::get('BASE_URL') ?: '/';
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function redirect($url, $status = null, $message = null, $duration = 10)
    {
        if ($status && $message) {
            $flashData = [
                'status' => $status,
                'message' => $message
            ];

            if ($duration > 0) {
                $flashData['expires_at'] = time() + $duration;
                $flashData['duration'] = $duration * 1000;
            }

            self::set_flash('notification', $flashData);
        }

        if (self::is_ajax()) {
            self::json_redirect($url);
        } else {
            header("Location: " . self::url($url));
            exit();
        }
    }

    public static function redirectToNotFound()
    {
        header("Location: " . self::url('/404'));
        exit();
    }

    public static function request($key = null, $default = null)
    {
        $requestData = array_merge($_GET, $_POST);
        return new class($requestData) {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function get($key = null, $default = null)
            {
                return $key === null ? $this->data : ($this->data[$key] ?? $default);
            }

            public function is($pattern)
            {
                $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $pattern = str_replace('*', '.*', $pattern);
                return preg_match("#^/?" . ltrim($pattern, '/') . "$#", $currentPath);
            }
        };
    }

    public static function set_flash($key, $message)
    {
        self::ensureSession();
        $_SESSION[$key] = $message;
    }

    public static function get_flash($key)
    {
        self::ensureSession();

        if (!isset($_SESSION[$key])) {
            return null;
        }

        $data = $_SESSION[$key];

        if (isset($data['expires_at']) && time() > $data['expires_at']) {
            unset($_SESSION[$key]);
            return null;
        }

        unset($_SESSION[$key]);
        return $data;
    }

    public static function session_get($key, $default = null)
    {
        self::ensureSession();
        return $_SESSION[$key] ?? $default;
    }

    public static function session_write($key, $value, $overwrite = false)
    {
        self::ensureSession();

        if ($overwrite || !isset($_SESSION[$key])) {
            $_SESSION[$key] = $value;
        } else {
            $_SESSION[$key] = array_merge($_SESSION[$key], $value);
        }
    }

    public static function session_destroy_all()
    {
        self::ensureSession();
        session_unset();
        session_destroy();
    }

    public static function validate_user_session()
    {
        self::ensureSession();

        if (!isset($_SESSION['user']['uid'])) return;

        $uid = $_SESSION['user']['uid'];
        $db = Database::getInstance();

        // Ambil data user berdasarkan UID
        $db->query("
                SELECT users.*, 
                roles.uid AS role_uniqId,
                roles.role_name AS role_name
                FROM users
                JOIN roles ON users.role_uid = roles.uid
                WHERE users.uid = :uid
            ");
        $db->bind(':uid', $uid);
        $user = $db->single();

        // ✅ Jika user tidak ditemukan (dihapus)
        if (!$user) {
            self::session_destroy_all();
            self::redirect('/login', 'error', 'User not found.');
        }

        if ($user && $user['status'] == '0') {
            self::session_destroy_all();
            self::redirect('/login', 'error', 'Status anda tidak lagi aktif');
        }

        if ($_SESSION['user']['role_name'] !== $user['role_name']) {
            $_SESSION['user']['role_name'] = $user['role_name'];
            $_SESSION['user']['role_uid'] = $user['role_uid']; // kalau mau

            // Redirect ke dashboard baru
            switch ($user['role_name']) {
                case $user['role_name']:
                    self::redirect('/dashboard/' . strtolower($user['role_name']), 'warning', 'Role anda sudah berganti');
            }
        }
    }

    public static function e($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function hash_password($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verify_password($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    public static function is_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function current_date($format = 'Y-m-d H:i:s')
    {
        date_default_timezone_set('Asia/Jakarta'); // Set timezone default untuk app Indonesia
        return date($format);
    }

    public static function rupiah($angka)
    {
        return "Rp " . number_format($angka, 0, ',', '.');
    }

    public static function random_string($length = 16)
    {
        return bin2hex(random_bytes(ceil($length / 2)));
    }

    public static function is_ajax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function json_redirect($url)
    {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => self::url($url)]);
        exit();
    }

    public static function is_post()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function is_get()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    public static function get_client_ip()
    {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                return filter_var($_SERVER[$key], FILTER_VALIDATE_IP);
            }
        }
        return '0.0.0.0';
    }

    public static function is_csrf()
    {
        $result = false;
        if (self::is_post()) {
            $sessionServer = $_SESSION['csrf_token'];
            $sessionForm = $_POST['_token'] ?? '';

            if ($sessionServer === $sessionForm) {
                $result = true;
            }

            return $result;
        }
    }

    public static function is_submit($name, $decision)
    {
        return isset($_POST[$name]) && ($_POST[$name] === $decision) ? true : false;
    }

    public static function uuid(int $length = 36)
    {
        // Upgrade ke UUID v4 standar (128-bit, tapi potong jika length <128)
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set variant
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return substr($uuid, 0, $length); // Potong jika length lebih pendek
    }

    public static function updateAt() {
        $time = Config::get("DB_TIMEZONE");
        $dt = new DateTime('now', new DateTimeZone($time));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function json($data = [], $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    // Fungsi baru: Generate CSRF token
    public static function generateCsrfToken()
    {
        self::ensureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Fungsi baru: Validate CSRF token
    public static function validateCsrfToken($token)
    {
        self::ensureSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Fungsi baru: Sanitasi input recursive (untuk array input)
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return trim(strip_tags($input));
    }

    // Fungsi baru: Handle file upload (dengan validasi)
    public static function uploadFile($fileKey, $targetDir, $allowedTypes = ['jpg', 'png', 'jpeg'], $maxSize = 2097152)
    { // 2MB default
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'No file uploaded or upload error.'];
        }

        $file = $_FILES[$fileKey];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedTypes)) {
            return ['error' => 'Invalid file type.'];
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'File too large.'];
        }

        $targetFile = $targetDir . '/' . self::random_string(20) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return ['success' => true, 'path' => $targetFile];
        }

        return ['error' => 'Failed to move file.'];
    }

    // Fungsi baru: Generate pagination links
    public static function paginate($totalItems, $perPage, $currentPage, $baseUrl)
    {
        $totalPages = ceil($totalItems / $perPage);
        $links = [];

        for ($i = 1; $i <= $totalPages; $i++) {
            $links[] = [
                'page' => $i,
                'url' => $baseUrl . '?page=' . $i,
                'active' => $i == $currentPage
            ];
        }

        return $links;
    }

    // Fungsi baru: Simple logging
    public static function log($message, $level = 'info')
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/app.log';
        $timestamp = self::current_date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND);
    }

    // Fungsi baru: Generate slug untuk URL (misalnya news title ke slug)
    public static function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text);
    }

    // Fungsi baru: Check user role cepat
    public static function hasRole($role)
    {
        self::ensureSession();
        return isset($_SESSION['user']['role_name']) && $_SESSION['user']['role_name'] === $role;
    }

    public static function authToken($data) {
        $_SESSION['auth_token'] = hash('sha256', $data . Config::get('APP_KEY'));
        // return $_SESSION['auth_token'];
    }
}


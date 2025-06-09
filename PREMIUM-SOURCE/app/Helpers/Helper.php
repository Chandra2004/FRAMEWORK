<?php
    namespace {{NAMESPACE}}\Helpers;

    use {{NAMESPACE}}\App\Config;

    class Helper {
        public static function url($path = '') {
            $baseUrl = Config::get('BASE_URL') ?: '/';
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        }

        public static function redirect($url, $status = null, $message = null) {
            // Simpan status dan message ke flash message jika ada
            if ($status && $message) {
                self::set_flash('notification', ['status' => $status, 'message' => $message]);
            }

            // Redirect tanpa parameter status dan message di URL
            header("Location: " . self::url($url));
            exit();
        }

        public static function redirectToNotFound() {
            header("Location: " . self::url('/404'));
            exit();
        }

        public static function request($key = null, $default = null) {
            $requestData = array_merge($_GET, $_POST);
            return $key === null ? $requestData : ($requestData[$key] ?? $default);
        }

        public static function set_flash($key, $message) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION[$key] = $message;
        }

        public static function get_flash($key) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            if (isset($_SESSION[$key])) {
                $message = $_SESSION[$key];
                unset($_SESSION[$key]);
                return $message;
            }
            return null;
        }

        public static function session_get($key, $default = null) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            return $_SESSION[$key] ?? $default;
        }

        public static function session_write($key, $value) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION[$key] = $value;
        }

        public static function session_destroy_all() {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            session_unset();
            session_destroy();
        }

        public static function e($string) {
            return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        }

        public static function hash_password($password) {
            return password_hash($password, PASSWORD_BCRYPT);
        }

        public static function verify_password($password, $hashedPassword) {
            return password_verify($password, $hashedPassword);
        }

        public static function is_email($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }

        public static function current_date($format = 'Y-m-d H:i:s') {
            return date($format);
        }

        public static function rupiah($angka) {
            return "Rp " . number_format($angka, 0, ',', '.');
        }

        public static function random_string($length = 16) {
            return bin2hex(random_bytes(ceil($length / 2)));
        }

        public static function is_post() {
            return $_SERVER['REQUEST_METHOD'] === 'POST';
        }

        public static function is_get() {
            return $_SERVER['REQUEST_METHOD'] === 'GET';
        }

        public static function get_client_ip() {
            $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
            foreach ($ip_keys as $key) {
                if (!empty($_SERVER[$key])) {
                    return filter_var($_SERVER[$key], FILTER_VALIDATE_IP);
                }
            }
            return '0.0.0.0';
        }

        public static function generateUUID() {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
    }
?>
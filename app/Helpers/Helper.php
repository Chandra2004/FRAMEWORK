<?php
    namespace {{NAMESPACE}}\Helpers;

    use {{NAMESPACE}}\App\Config;

    class Helper {
        public static function url($path = '') {
            $baseUrl = Config::get('BASE_URL') ?: '/';
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        }

        public static function redirect($url, $status = null, $message = null, $duration = 0) {
            if ($status && $message) {
                $flashData = [
                    'status' => $status,
                    'message' => $message
                ];

                if ($duration > 0) {
                    $flashData['expires_at'] = time() + $duration;
                }

                self::set_flash('notification', $flashData);
            }

            header("Location: " . self::url($url));
            exit();
        }


        public static function redirectToNotFound() {
            header("Location: " . self::url('/404'));
            exit();
        }

        public static function request($key = null, $default = null) {
            $requestData = array_merge($_GET, $_POST);
            return new class($requestData) {
                private $data;
    
                public function __construct($data) {
                    $this->data = $data;
                }
    
                public function get($key = null, $default = null) {
                    return $key === null ? $this->data : ($this->data[$key] ?? $default);
                }
    
                public function is($pattern) {
                    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                    $pattern = str_replace('*', '.*', $pattern);
                    return preg_match("#^/?" . ltrim($pattern, '/') . "$#", $currentPath);
                }
            };
        }

        public static function set_flash($key, $message) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION[$key] = $message;
        }

        public static function get_flash($key) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

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

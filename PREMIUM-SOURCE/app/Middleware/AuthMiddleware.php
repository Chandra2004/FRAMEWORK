<?php
    namespace {{NAMESPACE}}\Middleware;

    use {{NAMESPACE}}\App\Config;
    use {{NAMESPACE}}\App\SessionManager;
    use {{NAMESPACE}}\Helpers\Helper;

    class AuthMiddleware implements Middleware {
        public function before() {
            if (session_status() === PHP_SESSION_NONE) {
                SessionManager::startSecureSession();
            }

            if (!isset($_SESSION['user_id']) || !isset($_SESSION['auth_token'])) {
                header('Location: ' . Config::get('BASE_URL') . '/login?error=unauthenticated');
                exit();
            }

            // Validasi token autentikasi
            $storedToken = $_SESSION['auth_token'];
            $expectedToken = hash('sha256', $_SESSION['user_id'] . Config::get('APP_KEY') . $_SESSION['last_activity']);
            if (!hash_equals($storedToken, $expectedToken)) {
                SessionManager::destroySession();
                header('Location: ' . Config::get('BASE_URL') . '/login?error=invalid_token');
                exit();
            }

            // Validasi waktu kedaluwarsa (contoh: 1 jam)
            $sessionTimeout = 3600;
            if (time() - ($_SESSION['last_activity'] ?? 0) > $sessionTimeout) {
                SessionManager::destroySession();
                header('Location: ' . Config::get('BASE_URL') . '/login?error=session_expired');
                exit();
            }
        }
    }
?>
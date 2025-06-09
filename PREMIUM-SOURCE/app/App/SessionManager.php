<?php
    namespace {{NAMESPACE}}\App;

    use {{NAMESPACE}}\App\Config;

    class SessionManager {
        public static function startSecureSession() {
            if (session_status() === PHP_SESSION_NONE) {
                // Konfigurasi sesi aman
                ini_set('session.cookie_httponly', 1); // Cegah akses JavaScript ke cookie sesi
                ini_set('session.cookie_secure', 1);   // Hanya kirim cookie melalui HTTPS
                ini_set('session.use_strict_mode', 1); // Cegah session fixation
                ini_set('session.cookie_samesite', 'Strict'); // Cegah CSRF melalui cookie

                session_start();

                // Regenerasi session ID pada start untuk keamanan tambahan
                if (!isset($_SESSION['initiated'])) {
                    session_regenerate_id(true);
                    $_SESSION['initiated'] = true;
                }

                // Validasi user-agent untuk mencegah session hijacking
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                if (!isset($_SESSION['user_agent'])) {
                    $_SESSION['user_agent'] = $userAgent;
                } elseif ($_SESSION['user_agent'] !== $userAgent) {
                    self::destroySession();
                    header('Location: ' . Config::get('BASE_URL') . '/login?error=invalid_session');
                    exit;
                }

                // Terapkan timeout sesi (30 menit)
                $timeout = 30 * 60;
                if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                    self::destroySession();
                    header('Location: ' . Config::get('BASE_URL') . '/login?error=session_expired');
                    exit;
                }
                $_SESSION['last_activity'] = time();
            }
        }

        public static function regenerateSession() {
            session_regenerate_id(true);
        }

        public static function destroySession() {
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
?>
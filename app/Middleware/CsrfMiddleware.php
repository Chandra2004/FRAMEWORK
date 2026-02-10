<?php

namespace TheFramework\Middleware;

use TheFramework\App\Config;
use TheFramework\Helpers\Helper;
use TheFramework\Http\Controllers\Services\ErrorController;

class CsrfMiddleware implements Middleware
{
    public static function generateToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyToken($token)
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return !empty($sessionToken) && !empty($token) && hash_equals($sessionToken, $token);
    }

    public function before()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? '';

            if (!self::verifyToken($token)) {
                // Handling Elegan (JSON support & View Support)
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
                    || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

                http_response_code(403);

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => 'Session expired or Invalid CSRF Token', 'code' => 403]);
                    exit;
                }

                // Coba load view error 403 jika ada
                $baseDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2);
                $viewFile = $baseDir . '/resources/views/errors/403.blade.php';
                if (file_exists($viewFile)) {
                    // Quick include view (tanpa Blade Engine full untuk performa error)
                    // Atau bisa panggil View::render() jika class View sudah diload
                    require $viewFile;
                    exit;
                }

                die('<h1>403 Forbidden</h1><p>Sesi Anda telah berakhir atau Token CSRF tidak valid. Silakan refresh halaman.</p>');
            }
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

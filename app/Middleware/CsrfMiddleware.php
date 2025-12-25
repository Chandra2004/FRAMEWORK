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
                // Di sini kita bisa throw Exception dan biar ErrorController yg handle
                // Tapi untuk middleware, respon 403 langsung lebih cepat & aman
                http_response_code(403);
                die('403 Forbidden - CSRF Token Invalid');
            }
        }
    }
}

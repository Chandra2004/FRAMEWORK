<?php

namespace TheFramework\Middleware;

use TheFramework\App\Http\SessionManager;
use TheFramework\Helpers\Helper;

class AuthMiddleware implements Middleware
{
    public function before()
    {
        // Ensure session started
        if (session_status() === PHP_SESSION_NONE) {
            SessionManager::startSecureSession();
        }

        $sessionKey = \TheFramework\App\Core\Config::get('auth.session_key', 'user.uid');
        $userUid = session($sessionKey);
        $authToken = session('auth_token');

        // Basic check
        if (!$userUid || !$authToken) {
            return redirect('/login', 'error', 'Sesi Berakhir. Silakan login kembali.');
        }

        // Token validation (Second Layer)
        $storedToken = Helper::getAuthToken();

        if (!$storedToken || !Helper::validateAuthToken($storedToken, (string)$userUid)) {
            error_log("AuthMiddleware: Token mismatch or invalid for UID: $userUid");
            SessionManager::destroySession();
            return redirect('/login', 'error', 'Sesi tidak valid. Silakan login ulang.');
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

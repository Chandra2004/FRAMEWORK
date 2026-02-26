<?php

namespace TheFramework\Middleware;

use TheFramework\App\Http\SessionManager;
use TheFramework\Helpers\Helper;

class AuthMiddleware implements Middleware
{
    public function before()
    {
        // Pastikan session aktif
        if (session_status() === PHP_SESSION_NONE) {
            SessionManager::startSecureSession();
        }

        // Cek login dasar
        if (!session('user.uid') || !session('auth_token')) {
            return redirect('/login', 'error', 'Sesi Berakhir. Silakan login kembali.');
        }

        // Validasi token autentikasi (Keamanan Lapis Kedua)
        $storedToken = Helper::getAuthToken();
        $userUid = session('user.uid');

        if (!$storedToken || !Helper::validateAuthToken($storedToken, $userUid)) {
            error_log("AuthMiddleware: Token mismatch or not found for UID: $userUid");
            SessionManager::destroySession();
            return redirect('/login', 'error', 'Token tidak valid. Silakan login ulang.');
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

<?php

namespace TheFramework\Middleware;

use TheFramework\App\Config;
use TheFramework\App\SessionManager;
use TheFramework\Helpers\Helper;

class AuthMiddleware implements Middleware
{
    public function before()
    {
        if (session_status() === PHP_SESSION_NONE) {
            SessionManager::startSecureSession();
        }

        if (!isset($_SESSION['user']['uid']) || !isset($_SESSION['auth_token'])) {
            Helper::redirect('/login', 'error', 'You must be logged in to access this page.');
            exit();
        }

        // Validasi token autentikasi menggunakan Helper
        $storedToken = Helper::getAuthToken();
        $userUid = $_SESSION['user']['uid'] ?? '';

        if (!$storedToken || !Helper::validateAuthToken($storedToken, $userUid)) {
            error_log("AuthMiddleware: Token mismatch or not found.");
            SessionManager::destroySession();
            Helper::redirect('/login', 'error', 'Invalid authentication token.');
            exit();
        }

        if (empty($_SESSION['auth_token']) || empty($_SESSION['user']['uid'])) {
            SessionManager::destroySession();
            Helper::redirect('/login', 'error', 'You must be logged in to access this page.');
            exit();
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

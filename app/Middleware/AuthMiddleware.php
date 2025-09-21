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

        // Validasi token autentikasi
        $storedToken = $_SESSION['auth_token'];
        $expectedToken = hash('sha256', $_SESSION['user']['uid'] . Config::get('APP_KEY'));
        if (!hash_equals($storedToken, $expectedToken)) {
            error_log("AuthMiddleware: Token mismatch - Stored: $storedToken, Expected: $expectedToken");
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
}

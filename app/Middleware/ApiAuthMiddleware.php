<?php

namespace TheFramework\Middleware;

use TheFramework\App\RateLimiter;
use TheFramework\Helpers\Helper;
use TheFramework\App\Config;
use TheFramework\App\Database;

class ApiAuthMiddleware implements Middleware
{
    public function before()
    {
        // 0. Rate Limiting Protection (Anti DDoS / Brute Force)
        // Batasi 60 request per menit per IP
        $clientIp = Helper::get_client_ip();
        RateLimiter::check($clientIp, 60, 60);

        // ----------------------------------------------------
        // JALUR 1: INTERNAL WEB APP (via CSRF Token)
        // ----------------------------------------------------
        // Karena aplikasi ini TANPA LOGIN, kita validasi bahwa request 
        // berasal dari frontend kita sendiri menggunakan CSRF Token.
        if (session_status() !== PHP_SESSION_ACTIVE)
            session_start();

        $headers = getallheaders();
        $csrfToken = $headers['X-CSRF-TOKEN'] ?? $headers['X-Csrf-Token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

        if ($csrfToken && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            return; // Lolos! Ini request sah dari website sendiri.
        }
        // ----------------------------------------------------

        // ----------------------------------------------------
        // JALUR 2: EKSTERNAL APP (via Bearer Token)
        // ----------------------------------------------------
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        // Cek format Bearer Token
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            // Jika CSRF gagal DAN Token gagal -> Unauthorized
            Helper::json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid access. Use CSRF Token (Web) or Bearer Token (API).'
            ], 401);
            exit;
        }

        $token = $matches[1];

        // 3. (OPSI A) Cek Master Key via .env (Simple Protection)
        // Set API_SECRET_KEY=rahasia123 di .env anda
        $masterKey = Config::get('API_SECRET_KEY');
        if (!empty($masterKey) && hash_equals($masterKey, $token)) {
            return; // Lolos validasi
        }

        // 4. (OPSI B) Cek Token di Database User (Advanced Protection)
        // Pastikan tabel users punya kolom 'api_token'
        try {
            $db = Database::getInstance();
            // Cek apakah kolom api_token ada biar gak error kalau belum migrate
            // Note: Ini query check sederhana, idealnya skema sudah pasti
            $db->query("SELECT uid, name, email, role_uid FROM users WHERE api_token = :token LIMIT 1");
            $db->bind(':token', $token);
            $user = $db->single();

            if ($user) {
                // Simpan info user yang login via API agar bisa diakses di Controller
                // Akses via: Helper::request()->user() (perlu implementasi tambahan) atau $_REQUEST['user']
                $_REQUEST['user_api'] = $user;
                return; // Lolos
            }
        } catch (\Exception $e) {
            // Abaikan error DB jika kolom belum ada, fallback ke unauthorized
        }

        // 5. Jika semua cek gagal
        Helper::json([
            'status' => 'error',
            'message' => 'Unauthorized: Invalid access token'
        ], 401);
        exit;
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

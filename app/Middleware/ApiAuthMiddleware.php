<?php

namespace TheFramework\Middleware;

use TheFramework\App\Http\RateLimiter;
use TheFramework\App\Database\Database;

class ApiAuthMiddleware implements Middleware
{
    public function before()
    {
        // 0. Rate Limiting Protection (Anti DDoS)
        $clientIp = request()->ip();
        RateLimiter::check($clientIp, config('API_RATE_LIMIT', 60), 60);

        // ----------------------------------------------------
        // JALUR 1: INTERNAL WEB APP (via CSRF Token)
        // ----------------------------------------------------
        $csrfToken = request()->header('X-CSRF-TOKEN') ?? request()->header('X-Csrf-Token');

        if ($csrfToken && session('csrf_token') && hash_equals(session('csrf_token'), $csrfToken)) {
            return; // OK
        }

        // ----------------------------------------------------
        // JALUR 2: EKSTERNAL APP (via Bearer Token)
        // ----------------------------------------------------
        $token = request()->bearerToken();

        if (!$token) {
            return json([
                'status' => 'error',
                'message' => 'Unauthorized: Gunakan CSRF Token (Web) atau Bearer Token (API).'
            ], 401);
        }

        // 3. Cek Master Key (.env)
        $masterKey = config('API_SECRET_KEY');
        if (!empty($masterKey) && hash_equals($masterKey, $token)) {
            return; // OK
        }

        // 4. Database User Check
        try {
            $db = Database::getInstance();
            $db->query("SELECT uid, name, email, role_uid FROM users WHERE api_token = :token LIMIT 1");
            $db->bind(':token', $token);
            $user = $db->single();

            if ($user) {
                // Injek user ke request array agar bisa diakses di controller
                $_REQUEST['user_api'] = $user;
                return; // OK
            }
        } catch (\Exception $e) {
            // DB Error ignored for safety (likely table/column missing)
        }

        // 5. Final Fail
        return json([
            'status' => 'error',
            'message' => 'Unauthorized: Invalid access token'
        ], 401);
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

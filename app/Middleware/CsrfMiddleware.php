<?php

namespace TheFramework\Middleware;

class CsrfMiddleware implements Middleware
{
    public static function generateToken()
    {
        if (!session('csrf_token')) {
            session(['csrf_token' => bin2hex(random_bytes(32))]);
        }
        return session('csrf_token');
    }

    public static function verifyToken($token)
    {
        $sessionToken = session('csrf_token');
        return !empty($sessionToken) && !empty($token) && hash_equals($sessionToken, $token);
    }

    public function before()
    {
        // Periksa semua metode yang mengubah state (POST, PUT, PATCH, DELETE)
        if (!in_array(request()->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            $token = request('_token') ?? request()->header('X-CSRF-TOKEN') ?? request()->header('X-Csrf-Token');

            if (!self::verifyToken($token)) {
                if (request()->expectsJson()) {
                    return json([
                        'status' => 'error', 
                        'message' => 'Sesi Berakhir atau Token CSRF tidak valid.', 
                        'code' => 403
                    ], 403);
                }

                // Gunakan helper abort untuk handle view errors/403.blade.php secara otomatis
                return abort(403, 'Sesi Anda telah berakhir atau Token CSRF tidak valid. Silakan refresh halaman.');
            }
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

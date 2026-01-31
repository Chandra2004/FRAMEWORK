<?php

namespace TheFramework\Http\Controllers\Services;

use TheFramework\App\Config;
use TheFramework\App\View;

class ErrorController
{
    public static function error403()
    {
        http_response_code(403);
        $model = [];

        View::render('Internal::errors.403', $model);
    }

    public static function error404()
    {
        http_response_code(404);
        $model = [];

        View::render('Internal::errors.404', $model);
    }

    public static function error500()
    {
        http_response_code(500);
        $model = [];

        View::render('Internal::errors.500', $model);
    }

    public static function databaseError(\TheFramework\App\DatabaseException $e)
    {
        http_response_code(500);

        // Ambil nilai dari .env untuk ditampilkan
        Config::loadEnv();
        $env_values = [
            'DB_HOST' => Config::get('DB_HOST', 'not set'),
            'DB_PORT' => Config::get('DB_PORT', 'not set'),
            'DB_NAME' => Config::get('DB_NAME', 'not set'),
            'DB_USER' => Config::get('DB_USER', 'not set'),
            'DB_PASS' => Config::get('DB_PASS', 'not set') ? '***hidden***' : 'not set',
        ];

        View::render('Internal::errors.database', [
            'message' => $e->getMessage(),
            'config_errors' => $e->getConfigErrors(),
            'env_errors' => $e->getEnvErrors(),
            'is_required' => $e->isConnectionRequired(),
            'env_values' => $env_values,
        ]);
    }

    public static function payment()
    {
        $model = [];

        View::render('Internal::errors.payment', $model);
    }

    public static function maintenance()
    {
        $model = [];

        View::render('Internal::errors.maintenance', $model);
    }
}

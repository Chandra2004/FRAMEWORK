<?php
// ROUTER
use TheFramework\App\Router;

// MIDDLEWARE
use TheFramework\Middleware\ApiAuthMiddleware;
use TheFramework\Middleware\WAFMiddleware;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\Middleware\LanguageMiddleware;

// CONTROLLER
use TheFramework\Http\Controllers\HomeController;
use TheFramework\Http\Controllers\ApiHomeController;
// UTILITIES

Router::add('GET', '/', HomeController::class, 'Welcome', [WAFMiddleware::class, LanguageMiddleware::class]);
Router::add('GET', '/users', HomeController::class, 'Users', [WAFMiddleware::class, LanguageMiddleware::class]);

Router::group(
    [
        'prefix' => '/users',
        'middleware' => [
            CsrfMiddleware::class,
            WAFMiddleware::class,
            LanguageMiddleware::class
        ]
    ],
    function () {
        Router::add('POST', '/create', HomeController::class, 'CreateUser');
        Router::add('POST', '/update/{uid}', HomeController::class, 'UpdateUser');
        Router::add('POST', '/delete/{uid}', HomeController::class, 'DeleteUser');
        Router::add('GET', '/information/{uid}', HomeController::class, 'InformationUser');
    }
);

Router::group(
    [
        'prefix' => '/api',
        'middleware' => [
            ApiAuthMiddleware::class,
            LanguageMiddleware::class
        ]
    ],
    function () {
        Router::add('GET', '/users', ApiHomeController::class, 'Users');
        Router::add('GET', '/users/{uid}', ApiHomeController::class, 'InformationUser');
        Router::add('POST', '/users/create', ApiHomeController::class, 'CreateUser');
        Router::add('POST', '/users/update/{uid}', ApiHomeController::class, 'UpdateUser');
        Router::add('POST', '/users/delete/{uid}', ApiHomeController::class, 'DeleteUser');
    }
);

// --- 🛠️ ERROR PAGE PREVIEW (LOCAL ONLY) 🛠️ ---
if (\TheFramework\App\Config::get('APP_ENV') === 'local') {
    Router::group(['prefix' => '/test-error'], function () {
        Router::add('GET', '/403', function () {
            \TheFramework\Http\Controllers\Services\ErrorController::error403();
        });
        Router::add('GET', '/404', function () {
            \TheFramework\Http\Controllers\Services\ErrorController::error404();
        });
        Router::add('GET', '/500', function () {
            \TheFramework\Http\Controllers\Services\ErrorController::error500();
        });
        Router::add('GET', '/payment', function () {
            \TheFramework\Http\Controllers\Services\ErrorController::payment();
        });
        Router::add('GET', '/maintenance', function () {
            \TheFramework\Http\Controllers\Services\ErrorController::maintenance();
        });
        Router::add('GET', '/database', function () {
            throw new \TheFramework\App\DatabaseException(
                "Koneksi gagal ke 'framework_test'",
                500,
                null,
                ['DB_HOST' => 'localhost', 'DB_PORT' => '3306'],
                ['DB_NAME' => 'Kemungkinan Typo di .env']
            );
        });
        Router::add('GET', '/exception', function () {
            throw new Exception("Ini adalah contoh Pengecualian Sistem (Exception).");
        });
        Router::add('GET', '/fatal', function () {
            // Memicu ParseError (Fatal)
            eval ('syntax error here');
        });
    });
}

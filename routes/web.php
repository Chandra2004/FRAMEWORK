<?php
// ROUTER
use TheFramework\App\Http\Router;

// MIDDLEWARE
use TheFramework\Middleware\ApiAuthMiddleware;
use TheFramework\Middleware\WAFMiddleware;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\Middleware\LanguageMiddleware;

// CONTROLLER
use TheFramework\Http\Controllers\HomeController;

// UTILITIES
Router::get('/', HomeController::class, 'welcome')->middleware([WAFMiddleware::class, LanguageMiddleware::class]);
Router::get('/users', HomeController::class, 'users')->middleware([WAFMiddleware::class, LanguageMiddleware::class]);

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
        Router::post('/create', HomeController::class, 'createUser');
        Router::post('/update/{uid}', HomeController::class, 'updateUser');
        Router::post('/delete/{uid}', HomeController::class, 'deleteUser');
        Router::get('/information/{uid}', HomeController::class, 'informationUser');
    }
);

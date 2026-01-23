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


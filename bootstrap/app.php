<?php

use TheFramework\BladeInit;
use TheFramework\App\Config;
use TheFramework\App\Router;
use TheFramework\App\SessionManager;
use TheFramework\Http\Controllers\Services\FileController;
use TheFramework\Middleware\CsrfMiddleware;
use TheFramework\Middleware\WAFMiddleware;
use TheFramework\App\Container;
use TheFramework\App\Database;
use TheFramework\App\Request;

SessionManager::startSecureSession();
Config::loadEnv();

// --- 🌟 ALL-IN ERROR HANDLING SYSTEM 🌟 ---
// Menggunakan View Blade di resources/views/errors/ untuk semua tampilan
$env = $_ENV['APP_ENV'] ?? 'production';

// Helper function untuk membaca snippet code
$getSnippet = function ($file, $line, $linesAround = 10) {
    if (!file_exists($file) || !is_readable($file))
        return [];
    $lines = file($file);
    $start = max(0, $line - $linesAround - 1);
    $end = min(count($lines), $line + $linesAround);
    $snippet = [];
    for ($i = $start; $i < $end; $i++) {
        $snippet[$i + 1] = $lines[$i];
    }
    return $snippet;
};

// 1. Error Handler (Warning/Notice)
set_error_handler(function ($severity, $message, $file, $line) use ($env, $getSnippet) {
    if (!(error_reporting() & $severity))
        return;

    // Di Production, warning biasanya di-log saja, tidak ditampilkan
    if ($env === 'production') {
        error_log("[Warning] $message in $file:$line");
        return;
    }

    // Map severity code ke string
    $severityName = match ($severity) {
        E_WARNING => 'E_WARNING', E_NOTICE => 'E_NOTICE',
        E_USER_WARNING => 'E_USER_WARNING', E_USER_NOTICE => 'E_USER_NOTICE',
        E_DEPRECATED => 'E_DEPRECATED', E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        default => 'UNKNOWN_ERROR'
    };

    $data = [
        'severity_name' => $severityName,
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'code_snippet' => $getSnippet($file, $line),
        'error_code' => http_response_code(),
        'request_info' => [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        ]
    ];

    if (class_exists(\TheFramework\App\View::class)) {
        \TheFramework\App\View::render('errors.warning', $data);
    } else {
        echo "<b>Warning:</b> $message in $file on line $line";
    }
    exit; // Stop execution on warning in local to let user notice it
});

// 2. Exception Handler (Global)
set_exception_handler(function ($e) use ($env, $getSnippet) {
    $code = $e->getCode();
    $status = ($code >= 400 && $code < 600) ? $code : 500;
    http_response_code($status);

    // A. Database Exception
    if ($e instanceof \TheFramework\App\DatabaseException || str_contains(get_class($e), 'PDOException')) {
        \TheFramework\App\View::render('errors.database', [
            'message' => $e->getMessage(),
            'env_values' => $_ENV,
            'request_info' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'app_env' => $env
            ]
        ]);
        return;
    }

    // B. Production Error (Generic 500)
    if ($env === 'production') {
        // Coba render 500.blade.php jika status 500, atau 404/403 jika status match
        $view = match ($status) {
            404 => 'errors.404',
            403 => 'errors.403',
            default => 'errors.500'
        };
        \TheFramework\App\View::render($view);
        return;
    }

    // C. Local Debug (Exception Blade)
    // Persiapkan data lengkap untuk Ignition-style UI
    $trace = [];
    foreach ($e->getTrace() as $t) {
        $trace[] = [
            'function' => $t['function'] ?? '',
            'class' => $t['class'] ?? '',
            'type' => $t['type'] ?? '',
            'file' => $t['file'] ?? '',
            'line' => $t['line'] ?? '',
            'args' => array_map(fn($a) => gettype($a), $t['args'] ?? [])
        ];
    }

    $data = [
        'error_code' => $status,
        'error_code_text' => 'Exception',
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'code_snippet' => $getSnippet($e->getFile(), $e->getLine()),
        'trace_parsed' => $trace,
        'previous' => $e->getPrevious(),
        'request_info' => [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'query' => $_GET
        ],
        'environment' => [
            'php_version' => PHP_VERSION,
            'app_env' => $env,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
        ]
    ];

    \TheFramework\App\View::render('errors.exception', $data);
});

// 3. Fatal Error (Shutdown Function)
register_shutdown_function(function () use ($env, $getSnippet) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
        // Bersihkan buffer output sebelumnya (misal ada echo setengah jalan)
        if (ob_get_length())
            ob_end_clean();
        http_response_code(500);

        if ($env === 'production') {
            \TheFramework\App\View::render('errors.500');
            return;
        }

        $typeName = match ($error['type']) {
            E_ERROR => 'E_ERROR', E_PARSE => 'E_PARSE',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_CORE_ERROR => 'E_CORE_ERROR',
            default => 'FATAL_ERROR'
        };

        $data = [
            'error_code' => 500,
            'type_name' => $typeName,
            'type' => $error['type'],
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'code_snippet' => $getSnippet($error['file'], $error['line']),
            'request_info' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'app_env' => $env
            ]
        ];

        \TheFramework\App\View::render('errors.fatal', $data);
    }
});

// Setting Display Errors untuk PHP native (Backup)
ini_set('display_errors', 0); // Kita handle sendiri via View
error_reporting(E_ALL);

header('X-Powered-By: Native-Chandra');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Rate Limiting Global
TheFramework\App\RateLimiter::check($_SERVER['REMOTE_ADDR'], 100, 120);

CsrfMiddleware::generateToken();

$container = Container::getInstance();

$container->singleton(Database::class, function () {
    return Database::getInstance();
});

$container->singleton(Request::class, function () {
    return new Request();
});

Router::add('GET', '/file/(.*)', FileController::class, 'Serve');

$cacheFile = __DIR__ . '/../bootstrap/cache/routes.php';
if (file_exists($cacheFile)) {
    $routes = include $cacheFile;
    foreach ($routes as $route) {
        Router::add(
            $route['method'],
            $route['path_original'] ?? $route['path'],
            $route['handler'],
            $route['function'],
            $route['middleware']
        );
    }
} else {
    require_once __DIR__ . '/../routes/web.php';
}

BladeInit::init();
Router::run();

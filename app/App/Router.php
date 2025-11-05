<?php

namespace TheFramework\App;

use TheFramework\Http\Controllers\Services\ErrorController;
use TheFramework\Http\Controllers\Services\DebugController;
use Exception;

class Router
{
    private static array $routes = [];
    private static array $routeDefinitions = [];
    private static bool $routeFound = false;
    private static array $groupStack = [];

    /**
     * Tambah route baru (ikut group prefix dan middleware)
     */
    public static function add(string $method, string $path, $controllerOrCallback, string $function = null, array $middlewares = [])
    {
        $prefix = '';
        $groupMiddlewares = [];

    
        foreach (self::$groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefix .= rtrim($group['prefix'], '/');
            }
            if (!empty($group['middleware'])) {
                $groupMiddlewares = array_merge($groupMiddlewares, (array)$group['middleware']);
            }
        }

        $fullPath = $prefix . $path;

        $middlewares = array_merge($groupMiddlewares, $middlewares);

        $patternPath = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', '(?P<$1>[^/]+)', $fullPath);
        $compiledPattern = "#^" . $patternPath . "$#i";

        self::$routes[] = [
            'method'     => strtoupper($method),
            'path'       => $compiledPattern,
            'handler'    => $controllerOrCallback,
            'function'   => $function,
            'middleware' => $middlewares
        ];

        self::$routeDefinitions[] = [
            'method'     => strtoupper($method),
            'path'       => $fullPath,
            'handler'    => $controllerOrCallback,
            'function'   => $function,
            'middleware' => $middlewares
        ];
    }

    /**
     * Group routes dengan prefix dan/atau middleware
     */
    public static function group(array $attributes, callable $callback)
    {
        self::$groupStack[] = $attributes;
        call_user_func($callback);
        array_pop(self::$groupStack);
    }

    /**
     * Jalankan router
     */
    public static function run()
    {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        Config::loadEnv();
        self::registerErrorHandlers();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }

        $path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (preg_match('#^/assets/(.*)$#', $path, $matches)) {
            self::serveAsset($matches[1]);
            return;
        }

        self::checkAppMode();

        try {
            foreach (self::$routes as $route) {
                if ($method !== $route['method']) continue;

                if (preg_match($route['path'], $path, $matches)) {
                    foreach ($route['middleware'] as $middleware) {
                        $instance = is_array($middleware)
                            ? new $middleware[0](...array_slice($middleware, 1))
                            : new $middleware();
                        $instance->before();
                    }
                
                    $params = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string')));
                
                    if ($route['handler'] instanceof \Closure) {
                        call_user_func_array($route['handler'], $params);
                    } else {
                        if (!class_exists($route['handler'])) {
                            throw new Exception("Controller {$route['handler']} tidak ditemukan");
                        }
                        $controller = new $route['handler']();
                        $function   = $route['function'];
                        if (!method_exists($controller, $function)) {
                            throw new Exception("Method {$function} tidak ditemukan di {$route['handler']}");
                        }
                        call_user_func_array([$controller, $function], $params);
                    }

                    self::$routeFound = true;
                    return;
                }
            }

            if (!self::$routeFound) {
                self::handle404();
            }
        } catch (Exception $e) {
            self::handle500($e);
        }
    }

    /**
     * Daftar error handler
     */
    private static function registerErrorHandlers()
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) return;
            if (in_array($severity, [E_WARNING, E_USER_WARNING, E_NOTICE, E_USER_NOTICE])) {
                if (Config::get('APP_ENV') !== 'production') {
                    DebugController::showWarning([
                        'message' => $message,
                        'file'    => $file,
                        'line'    => $line
                    ]);
                }
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function ($e) {
            if (Config::get('APP_ENV') === 'production') {
                (new ErrorController())->error500();
            } else {
                DebugController::showException($e);
            }
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
                if (Config::get('APP_ENV') === 'production') {
                    (new ErrorController())->error500();
                } else {
                    DebugController::showFatal($error);
                }
            }
            ob_end_flush();
        });

        if (Config::get('APP_ENV') === 'production') {
            error_reporting(0);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    private static function checkAppMode()
    {
        $mode = Config::get('APP_ENV');
        $errorController = new ErrorController();

        if ($mode === 'maintenance') {
            $errorController->maintenance();
            exit;
        } elseif ($mode === 'payment') {
            $errorController->payment();
            exit;
        }
    }

    private static function serveAsset(string $filePath)
    {
        $fullPath = dirname(__DIR__, 2) . "/resources/$filePath";
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Asset not found: $filePath";
            exit;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            'eot'   => 'application/vnd.ms-fontobject',
            'ico'   => 'image/x-icon',
            'json', 'map' => 'application/json',
            default => mime_content_type($fullPath) ?: 'application/octet-stream'
        };

        header("Content-Type: $mime");
        readfile($fullPath);
        exit;
    }

    public static function handleAbort(string $message = "Akses ditolak")
    {
        http_response_code(403);
        if (Config::get('APP_ENV') === 'production') {
            (new ErrorController())->error403();
        } else {
            echo "<strong>403 Forbidden</strong><br>";
            echo "<strong>Alasan:</strong> $message<br>";
        }
        exit;
    }

    private static function handle500(Exception $e)
    {
        if (ob_get_length()) ob_end_clean();
        http_response_code(500);
        if (Config::get('APP_ENV') === 'production') {
            (new ErrorController())->error500();
        } else {
            DebugController::showException($e);
        }
        exit;
    }

    private static function handle404()
    {
        if (ob_get_length()) ob_end_clean();
        http_response_code(404);
        (new ErrorController())->error404();
        exit;
    }

    public static function getRouteDefinitions(): array
    {
        return self::$routeDefinitions;
    }

    public static function loadCachedRoutes(array $cachedRoutes)
    {
        foreach ($cachedRoutes as $route) {
            self::add($route['method'], $route['path'], $route['handler'], $route['function'], $route['middleware']);
        }
    }
}

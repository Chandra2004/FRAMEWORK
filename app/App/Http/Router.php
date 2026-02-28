<?php

namespace TheFramework\App\Http;

use TheFramework\App\Core\Config;
use TheFramework\App\Core\Container;
use TheFramework\App\Database\DatabaseException;
use TheFramework\App\Http\View;
use Exception;

class Router
{
    private static array $routes = [];
    private static array $routeDefinitions = [];
    private static bool $routeFound = false;
    private static array $groupStack = [];
    private static array $namedRoutes = [];
    private static ?array $fallbackRoute = null;

    // ==========================================================
    //  HTTP VERB HELPERS
    // ==========================================================

    public static function get(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        return self::add('GET', $path, $controllerOrCallback, $function);
    }

    public static function post(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        return self::add('POST', $path, $controllerOrCallback, $function);
    }

    public static function put(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        return self::add('PUT', $path, $controllerOrCallback, $function);
    }

    public static function patch(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        return self::add('PATCH', $path, $controllerOrCallback, $function);
    }

    public static function delete(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        return self::add('DELETE', $path, $controllerOrCallback, $function);
    }

    public static function options(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        return self::add('OPTIONS', $path, $controllerOrCallback, $function);
    }

    public static function any(string $path, $controllerOrCallback, ?string $function = null): Route
    {
        $route = self::add('ANY', $path, $controllerOrCallback, $function);
        return $route;
    }

    public static function view(string $path, string $view, array $data = []): Route
    {
        return self::get($path, function () use ($view, $data) {
            return \TheFramework\App\Http\View::render($view, $data);
        });
    }

    public static function redirect(string $uri, string $destination, int $status = 302): Route
    {
        return self::any($uri, function () use ($destination, $status) {
            \TheFramework\Helpers\Helper::redirect($destination, null, null, 0);
            exit;
        });
    }

    public static function fallback($controllerOrCallback, ?string $function = null)
    {
        self::$fallbackRoute = [
            'handler' => $controllerOrCallback,
            'function' => $function,
        ];
    }

    public static function add(string $method, string $path, $controllerOrCallback, ?string $function = null, array $middlewares = []): Route
    {
        $prefix = '';
        $groupMiddlewares = [];

        foreach (self::$groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefix .= rtrim($group['prefix'], '/');
            }
            if (!empty($group['middleware'])) {
                $groupMiddlewares = array_merge($groupMiddlewares, (array) $group['middleware']);
            }
        }

        $fullPath = $prefix . $path;
        $middlewares = array_merge($groupMiddlewares, $middlewares);

        $patternPath = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', '(?P<$1>[^/]+)', $fullPath);
        $compiledPattern = "#^" . $patternPath . "$#i";

        $index = count(self::$routes);

        $routeInfo = [
            'method' => strtoupper($method),
            'path' => $compiledPattern,
            'handler' => $controllerOrCallback,
            'function' => $function,
            'middleware' => $middlewares,
            'name' => null
        ];

        self::$routes[$index] = $routeInfo;

        self::$routeDefinitions[$index] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $controllerOrCallback,
            'function' => $function,
            'middleware' => $middlewares,
            'name' => null
        ];

        return new Route($index);
    }

    // ==========================================================
    //  ROUTE CLASS COMMUNICATION
    // ==========================================================

    public static function updateRoute(int $index, string $key, $value): void
    {
        if (isset(self::$routes[$index])) {
            self::$routes[$index][$key] = $value;
            self::$routeDefinitions[$index][$key] = $value;
        }

        if ($key === 'name') {
            self::$namedRoutes[$value] = $index;
        }
    }

    public static function appendMiddleware(int $index, array $middlewares): void
    {
        if (isset(self::$routes[$index])) {
            self::$routes[$index]['middleware'] = array_merge(self::$routes[$index]['middleware'], $middlewares);
            self::$routeDefinitions[$index]['middleware'] = array_merge(self::$routeDefinitions[$index]['middleware'], $middlewares);
        }
    }

    public static function group(array $attributes, callable $callback)
    {
        self::$groupStack[] = $attributes;
        call_user_func($callback);
        array_pop(self::$groupStack);
    }

    public static function resource(string $basePath, $controller, array $options = []): void
    {
        $basePath = rtrim($basePath, '/');
        $middlewares = $options['middleware'] ?? [];

        self::add('GET', $basePath, $controller, 'index', $middlewares);
        self::add('GET', $basePath . '/create', $controller, 'create', $middlewares);
        self::add('POST', $basePath, $controller, 'store', $middlewares);
        self::add('GET', $basePath . '/{id}', $controller, 'show', $middlewares);
        self::add('GET', $basePath . '/{id}/edit', $controller, 'edit', $middlewares);
        self::add('POST', $basePath . '/{id}', $controller, 'update', $middlewares);
        self::add('POST', $basePath . '/{id}/delete', $controller, 'destroy', $middlewares);
    }

    public static function run()
    {
        self::$routeFound = false; // Reset state untuk testing

        ob_start();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        Config::loadEnv();
        // self::registerErrorHandlers(); // REMOVED: Delegated to bootstrap/app.php

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Method Spoofing for PUT, PATCH, DELETE from HTML Forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        if (preg_match('#^/assets/(.*)$#', $path, $matches)) {
            // 🚀 PERFORMANCE MOD: Asset Serving Strategy
            // Di Production, Nginx/Apache harus dikonfigurasi menunjuk ke folder /public
            // PHP hanya melayani jika file fisik di public tidak ditemukan atau APP_ENV=local

            // Cek file di public/assets dulu
            $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
            $publicFile = $root . "/public/assets/" . $matches[1];
            if (file_exists($publicFile)) {
                // Biarkan webserver/browser mengakses langsung, tapi karena request sudah masuk ke PHP (artinya rewrite rule jalan),
                // kita bisa serve dari sini SEBAGAI FALLBACK jika webserver salah config.
                // Tapi idealnya, URL browser harusnya mengakses file fisik langsung.
                self::servePublicAsset($publicFile);
                return;
            }

            // Fallback ke resources (Development Mode Support)
            if (Config::get('APP_ENV') === 'local') {
                self::serveAsset($matches[1]);
                return;
            }
        }

        self::checkAppMode();

        // 🚀 PERFORMANCE MOD: Route Caching Check
        $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
        $cacheFile = $root . '/storage/cache/routes.php';
        if (file_exists($cacheFile) && Config::get('APP_ENV') !== 'local') {
            // Production mode + file cache ada -> Load Instant
            $cachedRoutes = require $cacheFile;
            self::$routes = []; // Reset pre-defined static routes (jika ada yang bocor)
            self::loadCachedRoutes($cachedRoutes);
        } else {
            // Development mode atau cache tidak ada -> Regex Parsing on-the-fly
            // Manual load route file karena tidak diload di tempat lain
            // REMOVED redundant require to prevent double registration since index.php already loads it
        }

        try {
            foreach (self::$routes as $route) {
                if ($route['method'] !== 'ANY' && $method !== $route['method'])
                    continue;

                if (preg_match($route['path'], $path, $matches)) {
                    $activeMiddlewares = [];
                    foreach ($route['middleware'] as $middleware) {
                        $instance = is_array($middleware)
                            ? new $middleware[0](...array_slice($middleware, 1))
                            : new $middleware();

                        $instance->before();
                        $activeMiddlewares[] = $instance;
                    }

                    $params = array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string')));

                    if ($route['handler'] instanceof \Closure) {
                        $reflection = new \ReflectionFunction($route['handler']);
                        $dependencies = Container::getInstance()->resolveDependencies($reflection->getParameters());
                        call_user_func_array($route['handler'], $params);

                    } else {
                        if (!class_exists($route['handler'])) {
                            throw new Exception("Controller {$route['handler']} tidak ditemukan");
                        }

                        $container = Container::getInstance();
                        $controller = $container->make($route['handler']);

                        $function = $route['function'];
                        if (!method_exists($controller, $function)) {
                            throw new Exception("Method {$function} tidak ditemukan di {$route['handler']}");
                        }

                        $reflectionMethod = new \ReflectionMethod($controller, $function);
                        $methodDependencies = $container->resolveDependencies($reflectionMethod->getParameters());

                        $finalArgs = [];
                        foreach ($reflectionMethod->getParameters() as $param) {
                            $name = $param->getName();
                            $type = $param->getType();

                            if (array_key_exists($name, $params)) {
                                $finalArgs[] = $params[$name];
                                unset($params[$name]);
                            } elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                                try {
                                    $finalArgs[] = $container->make($type->getName());
                                } catch (\Exception $e) {
                                    if ($param->isDefaultValueAvailable()) {
                                        $finalArgs[] = $param->getDefaultValue();
                                    } else {
                                        throw $e;
                                    }
                                }
                            } elseif ($param->isDefaultValueAvailable()) {
                                $finalArgs[] = $param->getDefaultValue();
                            } else {
                                $finalArgs[] = null;
                            }
                        }

                        call_user_func_array([$controller, $function], $finalArgs);
                    }

                    // Jalankan Middleware After secara reverse (LIFO)
                    foreach (array_reverse($activeMiddlewares) as $instance) {
                        $instance->after();
                    }

                    self::$routeFound = true;
                    return;
                }
            }

            if (!self::$routeFound) {
                if (self::$fallbackRoute) {
                    $handler = self::$fallbackRoute['handler'];
                    if ($handler instanceof \Closure) {
                        call_user_func($handler);
                    } else {
                        $container = Container::getInstance();
                        $controller = $container->make($handler);
                        $function = self::$fallbackRoute['function'] ?? '__invoke';
                        call_user_func([$controller, $function]);
                    }
                } else {
                    self::handle404();
                }
            }
        } catch (Exception $e) {
            // Throw ulang exception agar ditangkap oleh Global Handler di bootstrap/app.php
            throw $e;
        }
    }

    // Method registerErrorHandlers() dihapus total karena sudah ada di bootstrap

    private static function checkAppMode()
    {
        $mode = strtolower(Config::get('APP_ENV', 'production'));

        // 🛠️ MAINTENANCE MODE
        if ($mode === 'maintenance') {
            http_response_code(503);
            try {
                View::render('Internal::errors.maintenance', []);
            } catch (\Exception $e) {
                echo "<h1>503 Service Unavailable</h1><p>System is under maintenance.</p>";
            }
            exit;
        }

        // 💰 PAYMENT MODE
        if ($mode === 'payment') {
            http_response_code(402);
            try {
                View::render('Internal::errors.payment', []);
            } catch (\Exception $e) {
                echo "<h1>402 Payment Required</h1><p>Payment is required to access this service.</p>";
            }
            exit;
        }

        // 🧪 TESTING MODE
        if ($mode === 'testing') {
            // Disable error reporting override if needed or set specific testing headers
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }

        // 📝 OTHER MODES (local, staging, demo, qa, ci, foo, etc)
        // You can add custom logic here if needed for specific environments
    }

    /**
     * Memastikan file yang diakses berada di dalam direktori yang diizinkan.
     * Mencegah Path Traversal (../../.env)
     */
    private static function isPathSecure(string $targetPath, string $baseDir): bool
    {
        $realBasePath = realpath($baseDir);
        $realTargetPath = realpath($targetPath);

        // Jika file tidak ada atau gagal di-realpath, anggap tidak aman
        if ($realTargetPath === false || $realBasePath === false) {
            return false;
        }

        // Cek apakah realpath target dimulai dengan realpath base
        return str_starts_with($realTargetPath, $realBasePath);
    }

    private static function servePublicAsset(string $fullPath)
    {
        // Security Check: Pastikan file ada di folder public/assets
        $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
        $publicDir = $root . "/public/assets";
        if (!self::isPathSecure($fullPath, $publicDir)) {
            http_response_code(403);
            die("Access Denied: Invalid asset path");
        }

        if (!file_exists($fullPath)) {
            http_response_code(404);
            return;
        }

        $mime = self::getMimeType($fullPath);
        header("Content-Type: $mime");
        readfile($fullPath);
    }

    private static function serveAsset(string $filePath)
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
        $resourcesDir = $root . "/resources";
        $fullPath = $resourcesDir . "/$filePath";

        // Security Check: Pastikan file ada di folder resources
        if (!self::isPathSecure($fullPath, $resourcesDir)) {
            http_response_code(403);
            die("Access Denied: Invalid asset path");
        }

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Asset not found: $filePath";
            return;
        }

        $mime = self::getMimeType($fullPath);
        header("Content-Type: $mime");
        readfile($fullPath);
    }

    private static function getMimeType($filePath)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'ico' => 'image/x-icon',
            'json', 'map' => 'application/json',
            default => mime_content_type($filePath) ?: 'application/octet-stream'
        };
    }

    public static function handleAbort(string $message = "Akses ditolak")
    {
        http_response_code(403);
        try {
            View::render('Internal::errors.403', ['message' => $message]);
        } catch (\Exception $e) {
            echo "<h1>403 Forbidden</h1><p>" . htmlspecialchars($message) . "</p>";
        }
    }

    private static function handle500(Exception $e)
    {
        // Delegasikan ke global handler dengan re-throw
        throw $e;
    }

    private static function handle404()
    {
        if (ob_get_length())
            ob_end_clean();
        http_response_code(404);

        try {
            View::render('Internal::errors.404');
        } catch (\Exception $e) {
            echo "<h1>404 Not Found</h1>";
        }
    }

    public static function getRouteDefinitions(): array
    {
        return self::$routeDefinitions;
    }

    public static function getRoutes(): array
    {
        return self::$routeDefinitions;
    }

    public static function loadCachedRoutes(array $cachedRoutes)
    {
        foreach ($cachedRoutes as $route) {
            self::add($route['method'], $route['path'], $route['handler'], $route['function'], $route['middleware']);
        }
    }

    public static function cacheRoutes()
    {
        // TODO: Implement route caching logic
        // File ini dipanggil oleh composer scripts
    }
}

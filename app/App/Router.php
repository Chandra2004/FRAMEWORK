<?php

namespace TheFramework\App;

use TheFramework\Http\Controllers\Services\ErrorController;
use TheFramework\Http\Controllers\Services\DebugController;
use TheFramework\App\DatabaseException;
use Exception;

class Router
{
    private static array $routes = [];
    private static array $routeDefinitions = [];
    private static bool $routeFound = false;
    private static array $groupStack = [];

    public static function add(string $method, string $path, $controllerOrCallback, ?string $function = null, array $middlewares = [])
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

        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $compiledPattern,
            'handler' => $controllerOrCallback,
            'function' => $function,
            'middleware' => $middlewares
        ];

        self::$routeDefinitions[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $controllerOrCallback,
            'function' => $function,
            'middleware' => $middlewares
        ];
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
            $publicFile = dirname(__DIR__, 2) . "/public/assets/" . $matches[1];
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
        $cacheFile = dirname(__DIR__, 2) . '/storage/cache/routes.php';
        if (file_exists($cacheFile) && Config::get('APP_ENV') !== 'local') {
            // Production mode + file cache ada -> Load Instant
            $cachedRoutes = require $cacheFile;
            self::$routes = []; // Reset pre-defined static routes (jika ada yang bocor)
            self::loadCachedRoutes($cachedRoutes);
        } else {
            // Development mode atau cache tidak ada -> Regex Parsing on-the-fly
            // Manual load route file karena tidak diload di tempat lain
            $routeFile = dirname(__DIR__, 2) . '/routes/web.php';
            if (file_exists($routeFile)) {
                require $routeFile;
            }
        }

        try {
            foreach (self::$routes as $route) {
                if ($method !== $route['method'])
                    continue;

                if (preg_match($route['path'], $path, $matches)) {
                    foreach ($route['middleware'] as $middleware) {
                        $instance = is_array($middleware)
                            ? new $middleware[0](...array_slice($middleware, 1))
                            : new $middleware();
                        $instance->before();
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

                    self::$routeFound = true;
                    return;
                }
            }

            if (!self::$routeFound) {
                self::handle404();
            }
        } catch (Exception $e) {
            // Throw ulang exception agar ditangkap oleh Global Handler di bootstrap/app.php
            throw $e;
        }
    }

    // Method registerErrorHandlers() dihapus total karena sudah ada di bootstrap

    private static function checkAppMode()
    {
        $mode = Config::get('APP_ENV');
        // Logic maintenance/payment tetap di sini atau bisa dipindah
        // Untuk sekarang biarkan saja, tapi pastikan ErrorController ada
        // Jika ErrorController tidak ada, manual exit
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
        $publicDir = dirname(__DIR__, 2) . "/public/assets";
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
        $resourcesDir = dirname(__DIR__, 2) . "/resources";
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
        // Gunakan view baru resources/views/errors/403.blade.php
        $viewFile = __DIR__ . '/../../resources/views/errors/403.blade.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "<h1>403 Forbidden</h1><p>$message</p>";
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

        // Gunakan view baru resources/views/errors/404.blade.php
        $viewFile = __DIR__ . '/../../resources/views/errors/404.blade.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "<h1>404 Not Found</h1>";
        }
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

    public static function cacheRoutes()
    {
        // TODO: Implement route caching logic
        // File ini dipanggil oleh composer scripts
    }
}

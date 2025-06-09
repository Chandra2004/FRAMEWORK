<?php
    namespace {{NAMESPACE}}\App;

    use {{NAMESPACE}}\App\Config;
    use {{NAMESPACE}}\Http\Controllers\ErrorController;
    use Exception;

    class Router {
        private static array $routes = [];
        private static bool $routeFound = false;
    
        public static function add(string $method, string $path, string $controller, string $function, array $middlewares = []) {
            $patternPath = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $path);
            $compiledPattern = "#^" . $patternPath . "$#i";
    
            self::$routes[] = [
                'method' => strtoupper($method),
                'path' => $compiledPattern,
                'controller' => $controller,
                'function' => $function,
                'middleware' => $middlewares
            ];
        }
    
        public static function run() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
    
            Config::loadEnv();
    
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization');
                exit;
            }
    
            // Pengecekan APP_ENV untuk maintenance atau payment
            $appEnv = Config::get('APP_ENV');
            $errorController = new ErrorController();
    
            if ($appEnv === 'maintenance') {
                $errorController->maintenance();
                exit;
            } elseif ($appEnv === 'payment') {
                $errorController->payment();
                exit;
            }
    
            // Pengaturan error reporting berdasarkan APP_ENV
            if ($appEnv === 'production') {
                error_reporting(0);
                ini_set('display_errors', '0');
                ini_set('log_errors', '1');
            } else {
                error_reporting(E_ALL);
                ini_set('display_errors', '1');
            }
    
            $path = $_SERVER['PATH_INFO'] ?? '/';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
            try {
                foreach (self::$routes as $route) {
                    if ($method !== $route['method']) continue;
    
                    if (preg_match($route['path'], $path, $matches)) {
                        foreach ($route['middleware'] as $middleware) {
                            $instance = new $middleware();
                            $instance->before();
                        }
    
                        if (!class_exists($route['controller'])) {
                            throw new Exception("Controller {$route['controller']} tidak ditemukan");
                        }
    
                        $controller = new $route['controller']();
                        $function = $route['function'];
    
                        if (!method_exists($controller, $function)) {
                            throw new Exception("Method {$function} tidak ditemukan di {$route['controller']}");
                        }
    
                        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    
                        call_user_func_array([$controller, $function], $params);
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
    
        private static function handleAbort($message = "Akses ditolak") {
            http_response_code(403);
    
            if (Config::get('APP_ENV') !== 'production') {
                echo "<strong>403 Forbidden</strong><br>";
                echo "<strong>Alasan:</strong> $message<br>";
            } else {
                echo "Akses ditolak";
            }
    
            exit;
        }
    
        private static function handle500(Exception $e) {
            http_response_code(500);
    
            if (Config::get('APP_ENV') === 'production') {
                $controller = new ErrorController();
                $controller->error500();
            } else {
                echo "Error 500: Internal Server Error<br>";
                echo "<strong>Pesan:</strong> " . $e->getMessage() . "<br>";
                echo "<strong>File:</strong> " . $e->getFile() . "<br>";
                echo "<strong>Baris:</strong> " . $e->getLine() . "<br>";
            }
    
            exit;
        }
    
        private static function handle404() {
            http_response_code(404);
            $controller = new ErrorController();
            $controller->error404();
            exit;
        }
    
        public static function cacheRoutes() {
            if (Config::get('APP_ENV') === 'production') {
                $cacheDir = __DIR__ . '/../Storage/cache';
                $cacheFile = $cacheDir . '/routes.cache';
    
                if (!file_exists($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
    
                if (!file_exists($cacheFile)) {
                    file_put_contents($cacheFile, '<?php return ' . var_export(self::$routes, true) . ';');
                }
            }
        }
    }
?>
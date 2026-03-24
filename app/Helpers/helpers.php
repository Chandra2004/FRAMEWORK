<?php

use TheFramework\Helpers\Helper;
use TheFramework\Helpers\Str;
use TheFramework\Helpers\Arr;
use TheFramework\Helpers\Collection;
use TheFramework\App\Core\Config;

/**
 * Global Helpers - The Framework v5.0 (Paten All-In)
 * 
 * CATATAN PENTING:
 * - redirect() dan keluarganya (back, to) memanggil exit() secara internal.
 * - abort() melempar HttpException (terminal).
 */

if (!function_exists('url')) {
    function url(string $path = '')
    {
        return Helper::url($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path = '')
    {
        $url = Helper::url('assets/' . ltrim($path, '/'));

        // Auto-Versioning (Cache Busting - "Paten" Feature)
        $fullPath = base_path('public/assets/' . ltrim($path, '/'));
        if (file_exists($fullPath)) {
            $version = filemtime($fullPath);
            $url .= '?v=' . $version;
        }

        return $url;
    }
}

if (!function_exists('redirect')) {
    function redirect(?string $url = null, ?string $status = null, ?string $message = null)
    {
        if ($url === null) {
            return new class {
                public function to(string $url, ?string $status = null, ?string $message = null)
                {
                    return Helper::redirect($url, $status, $message);
                }
                public function back(?string $status = null, ?string $message = null)
                {
                    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                    return Helper::redirect($referer, $status, $message);
                }
            };
        }
        return Helper::redirect($url, $status, $message);
    }
}

if (!function_exists('back')) {
    function back(?string $status = null, ?string $message = null)
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        Helper::redirect($referer, $status, $message);
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [])
    {
        return \TheFramework\App\Http\View::render($name, $data);
    }
}

if (!function_exists('view_path')) {
    /**
     * Dapatkan path absolut ke direktori view atau file view tertentu.
     */
    function view_path(string $path = '')
    {
        return base_path('resources/views' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('request')) {
    /**
     * Get the Request instance or a specific input value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return \TheFramework\App\Http\Request|mixed
     */
    function request($key = null, $default = null)
    {
        return Helper::request($key, $default);
    }
}

if (!function_exists('session')) {
    function session($key = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            try {
                \TheFramework\App\Http\SessionManager::startSecureSession();
            } catch (\Throwable $e) {
                // Ignore if session cannot be started
            }
        }

        if ($key === null) {
            return $_SESSION;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
            return null;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param  string|object  $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('cache')) {
    /**
     * Fluent Global Cache Helper — The Framework v5.1.0
     * 
     * Contoh: cache('key') -> get
     *         cache(['key' => 'val']) -> put
     *         cache() -> CacheManager instance
     */
    function cache(string|array|null $key = null, $default = null)
    {
        if ($key === null) {
            return new class {
                public function __call($method, $args)
                {
                    return \TheFramework\App\Cache\CacheManager::$method(...$args);
                }
            };
        }

        if (is_array($key)) {
            return \TheFramework\App\Cache\CacheManager::putMany($key);
        }

        return \TheFramework\App\Cache\CacheManager::get($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key)
    {
        return Helper::get_flash($key);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return Helper::generateCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        $token = $_SESSION['csrf_token'] ?? '';
        $token = is_string($token) ? $token : '';
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method)
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('e')) {
    function e($value)
    {
        return Helper::e($value);
    }
}

if (!function_exists('error')) {
    function error(?string $field = null)
    {
        return Helper::validation_errors($field);
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field)
    {
        return Helper::has_error($field);
    }
}

if (!function_exists('old')) {
    function old(?string $field = null, $default = null)
    {
        return Helper::old($field, $default);
    }
}

if (!function_exists('collect')) {
    function collect($items = [])
    {
        return new Collection($items);
    }
}

if (!function_exists('now')) {
    function now(string $timezone = 'Asia/Jakarta')
    {
        return new DateTime('now', new DateTimeZone($timezone));
    }
}

if (!function_exists('tap')) {
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new class($value) {
                public $target;
                public function __construct($target) { $this->target = $target; }
                public function __call($method, $parameters)
                {
                    $this->target->{$method}(...$parameters);
                    return $this->target;
                }
            };
        }
        $callback($value);
        return $value;
    }
}

if (!function_exists('data_get')) {
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        foreach (is_array($key) ? $key : explode('.', $key) as $segment) {
            if (is_array($target)) {
                if (array_key_exists($segment, $target)) {
                    $target = $target[$segment];
                } else {
                    return $default;
                }
            } elseif ($target instanceof ArrayAccess) {
                if ($target->offsetExists($segment)) {
                    $target = $target[$segment];
                } else {
                    return $default;
                }
            } elseif (is_object($target)) {
                if (isset($target->{$segment})) {
                    $target = $target->{$segment};
                } elseif (method_exists($target, 'getAttribute')) {
                    $target = $target->getAttribute($segment);
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if (!function_exists('value')) {
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('optional')) {
    function optional($value = null, callable $callback = null)
    {
        if (is_callable($callback)) {
            return is_null($value) ? null : $callback($value);
        }

        return new class($value) {
            private $target;
            public function __construct($target) { $this->target = $target; }
            public function __get($key) { return $this->target->{$key} ?? null; }
            public function __call($method, $parameters)
            {
                return is_object($this->target) ? $this->target->{$method}(...$parameters) : null;
            }
        };
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = '')
    {
        throw new \TheFramework\App\Exceptions\HttpException($code, $message);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = '')
    {
        return defined('BASE_PATH') ? BASE_PATH . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '') : $path;
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = '')
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('__tf_dump')) {
    function __tf_dump($var, $depth = 0)
    {
        if ($depth > 10) {
            return '<span style="color: #EF4444; font-weight: bold;">*MAX DEPTH REACHED*</span>';
        }

        if (is_null($var)) {
            return '<span style="color: #EF4444; font-weight: bold;">null</span>';
        } elseif (is_bool($var)) {
            return '<span style="color: #F59E0B; font-weight: bold;">' . ($var ? 'true' : 'false') . '</span>';
        } elseif (is_int($var) || is_float($var)) {
            return '<span style="color: #F97316; font-weight: bold;">' . $var . '</span>';
        } elseif (is_string($var)) {
            $value = htmlspecialchars($var);
            return '<span style="color: #10B981;">"' . $value . '"</span> <span style="color: #6B7280; font-size: 0.85em;">(' . strlen($var) . ')</span>';
        } elseif (is_array($var)) {
            if (empty($var)) return '<span style="color: #9CA3AF;">[]</span>';
            $id = uniqid('dump_');
            $html = '<span style="color: #60A5FA; cursor: pointer; font-weight:bold; user-select:none;" onclick="var el=document.getElementById(\''.$id.'\'); el.style.display=el.style.display===\'none\'?\'block\':\'none\'"><span style="color:#9CA3AF; font-size:0.8em; margin-right:3px;">▶</span>array:' . count($var) . ' [</span>';
            $html .= '<div id="'.$id.'" style="display: block; margin-left: 20px; border-left: 1px dotted #4B5563; padding-left: 10px; margin-top:2px;">';
            foreach ($var as $k => $v) {
                $html .= '<div style="margin: 3px 0; line-height:1.4;">';
                $html .= '<span style="color: #D97706; font-weight:bold;">' . (is_string($k) ? '"'.htmlspecialchars($k).'"' : $k) . '</span> <span style="color: #6B7280;">=></span> ';
                $html .= __tf_dump($v, $depth + 1);
                $html .= '</div>';
            }
            $html .= '</div><span style="color: #60A5FA;">]</span>';
            return $html;
        } elseif (is_object($var)) {
            $class = get_class($var);
            $id = uniqid('dump_');
            $html = '<span style="color: #34D399; cursor: pointer; font-weight:bold; user-select:none;" onclick="var el=document.getElementById(\''.$id.'\'); el.style.display=el.style.display===\'none\'?\'block\':\'none\'"><span style="color:#9CA3AF; font-size:0.8em; margin-right:3px;">▶</span>' . $class . ' {#'.spl_object_id($var).'</span>';
            $html .= '<div id="'.$id.'" style="display: block; margin-left: 20px; border-left: 1px dotted #4B5563; padding-left: 10px; margin-top:2px;">';
            
            if (method_exists($var, '__debugInfo')) {
                $properties = $var->__debugInfo();
                foreach ($properties as $name => $val) {
                    $html .= '<div style="margin: 3px 0; line-height:1.4;">';
                    $html .= '<span style="color: #D97706; font-weight:bold;">"' . htmlspecialchars($name) . '"</span> <span style="color: #6B7280;">=></span> ';
                    $html .= __tf_dump($val, $depth + 1);
                    $html .= '</div>';
                }
            } else {
                $reflection = new \ReflectionClass($class);
                $properties = [];
                foreach ($reflection->getProperties() as $property) {
                    $property->setAccessible(true);
                    $name = $property->getName();
                    $visibility = $property->isPrivate() ? '-' : ($property->isProtected() ? '#' : '+');
                    try {
                        $val = $property->isInitialized($var) ? $property->getValue($var) : '*UNINITIALIZED*';
                    } catch (\Throwable $e) {
                        $val = '*ERROR*';
                    }
                    $html .= '<div style="margin: 3px 0; line-height:1.4;">';
                    $html .= '<span style="color: #9CA3AF; font-size:0.9em; margin-right:4px;">' . $visibility . '</span><span style="color: #D97706; font-weight:bold;">"' . htmlspecialchars($name) . '"</span> <span style="color: #6B7280;">=></span> ';
                    if ($val === '*UNINITIALIZED*') {
                        $html .= '<span style="color: #9CA3AF; font-style:italic;">uninitialized</span>';
                    } elseif ($val === '*ERROR*') {
                        $html .= '<span style="color: #EF4444; font-style:italic;">error accessing</span>';
                    } else {
                        $html .= __tf_dump($val, $depth + 1);
                    }
                    $html .= '</div>';
                }
            }
            $html .= '</div><span style="color: #34D399;">}</span>';
            return $html;
        } elseif (is_resource($var)) {
            return '<span style="color: #3B82F6; font-weight:bold;">resource(' . get_resource_type($var) . ')</span>';
        }

        return '<span style="color: #D1D5DB;">unknown type</span>';
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die - Premium Native Version (Laravel Alike)
     */
    function dd(...$vars)
    {
        if (php_sapi_name() === 'cli') {
            foreach ($vars as $v) {
                var_dump($v);
            }
            die();
        }

        echo '<div style="background:#18171B; color:#A9A9B3; padding:20px; font-family:Menlo, Monaco, Consolas, monospace; font-size:14px; border-top:5px solid #F97316; margin:0; width:100%; height:100vh; overflow:auto; box-sizing:border-box; position:absolute; top:0; left:0; z-index:999999;">';
        echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">';
        echo '<strong style="color:#FFF;">THE FRAMEWORK BUG HUNTER</strong>';
        
        $trace = debug_backtrace()[0];
        $file = str_replace(defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2), '', $trace['file']);
        echo '<span style="color:#9CA3AF; background:#2D2C31; padding:4px 8px; border-radius:4px; font-size:12px;">'.$file.':'.$trace['line'].'</span>';
        echo '</div>';
        
        foreach ($vars as $var) {
            echo '<div style="margin-bottom:15px; background:#1E1D21; padding:15px; border-radius:6px; box-shadow:inset 0 1px 3px rgba(0,0,0,0.5); overflow-x:auto;">';
            echo __tf_dump($var);
            echo '</div>';
        }
        
        echo '</div>';
        die();
    }
}

if (!function_exists('response')) {
    /**
     * Create a new HTTP Response instance.
     */
    function response(): \TheFramework\App\Http\Response
    {
        return new \TheFramework\App\Http\Response();
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route.
     */
    function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return \TheFramework\App\Http\Router::url($name, $parameters, $absolute);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the available auth instance or user.
     */
    function auth()
    {
        return new class {
            public function user() { return \TheFramework\App\Auth\AuthManager::user(); }
            public function check() { return \TheFramework\App\Auth\AuthManager::check(); }
            public function guest() { return \TheFramework\App\Auth\AuthManager::guest(); }
            public function id() { return \TheFramework\App\Auth\AuthManager::id(); }
            public function logout() { return \TheFramework\App\Auth\AuthManager::logout(); }
        };
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     */
    function event(string|object ...$args)
    {
        return \TheFramework\App\Events\Dispatcher::dispatch(...$args);
    }
}

if (!function_exists('json')) {
    function json(array $data, int $status = 200)
    {
        return Helper::json($data, $status);
    }
}

if (!function_exists('app_locale')) {
    function app_locale()
    {
        return \TheFramework\App\Core\Lang::getLocale();
    }
}

if (!function_exists('rupiah')) {
    function rupiah($number)
    {
        return Helper::rupiah($number);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null)
    {
        return \TheFramework\App\Core\Lang::get($key, $replace, $locale);
    }
}

if (!function_exists('trans')) {
    function trans(string $key, array $replace = [], ?string $locale = null)
    {
        return \TheFramework\App\Core\Lang::trans($key, $replace, $locale);
    }
}

if (!function_exists('trans_choice')) {
    function trans_choice(string $key, int $count, array $replace = [], ?string $locale = null)
    {
        return \TheFramework\App\Core\Lang::choice($key, $count, $replace, $locale);
    }
}

// ─── RBAC & Auth View Helpers ───────────────────────────────────────
if (!function_exists('auth_user')) {
    /**
     * Dapatkan instance user yang sedang login.
     */
    function auth_user()
    {
        return \TheFramework\App\Auth\AuthManager::user();
    }
}

if (!function_exists('can')) {
    /**
     * Cek permission/ability menggunakan Gate.
     */
    function can(string $ability, mixed ...$arguments): bool
    {
        return \TheFramework\App\Auth\Gate::allows($ability, ...$arguments);
    }
}

if (!function_exists('cannot')) {
    /**
     * Cek permission/ability menggunakan Gate.
     */
    function cannot(string $ability, mixed ...$arguments): bool
    {
        return \TheFramework\App\Auth\Gate::denies($ability, ...$arguments);
    }
}

if (!function_exists('is_role')) {
    /**
     * Cek role menggunakan RBAC helper pada user model.
     */
    function is_role(string ...$roles): bool
    {
        $user = auth_user();
        return $user && method_exists($user, 'hasRole') && $user->hasRole(...$roles);
    }
}

if (!function_exists('tfwire')) {
    /**
     * Render TFWire Component — The Turbo-Powered Livewire.
     * 
     * Usage:
     *   {!! tfwire(UserTable::class) !!}
     *   {!! tfwire(UserTable::class, 'custom_id') !!}
     *   {!! tfwire(UserTable::class, 'custom_id', ['status' => 'active']) !!}
     * 
     * @param string $componentClass  FQCN dari komponen
     * @param string|null $id         ID kustom untuk Turbo Frame
     * @param array $params           Parameter untuk method mount()
     */
    function tfwire(string $componentClass, ?string $id = null, array $params = []): string
    {
        // 🔥 AUTO-RESOLVE: Jika class tidak ditemukan, coba cari di folder Component default.
        if (!class_exists($componentClass)) {
            $namespacedClass = "\\TheFramework\\Components\\" . ltrim($componentClass, '\\');
            if (class_exists($namespacedClass)) {
                $componentClass = $namespacedClass;
            } else {
                // Fallback terakhir: Cek di App\Components jika namespace eksplisit dilewatkan
                $namespacedClass = "\\App\\Components\\" . ltrim($componentClass, '\\');
                if (class_exists($namespacedClass)) {
                    $componentClass = $namespacedClass;
                } else {
                    return "<!-- TFWire Error: Component [{$componentClass}] tidak ditemukan -->";
                }
            }
        }

        try {
            /** @var \TheFramework\App\TFWire\Component $component */
            $instance = new $componentClass($id);

            // 🌳 NESTED SUPPORT: Lacak parent component via Engine
            $parent = \TheFramework\App\TFWire\TFWireEngine::getParent();
            if ($parent) {
                $instance->setParent($parent);
            }

            // Masukkan ke stack sebelum render
            \TheFramework\App\TFWire\TFWireEngine::pushComponent($instance);

            // Jalankan mount
            $instance->mount(...$params);

            // Render komponen
            $html = $instance->render();

            // Keluarkan dari stack setelah selesai render
            \TheFramework\App\TFWire\TFWireEngine::popComponent();

            return $html;
        } catch (\Throwable $e) {
            if (config('app.debug', false)) {
                return "<!-- TFWire Error: " . htmlspecialchars($e->getMessage()) . " in " . basename($e->getFile()) . ":" . $e->getLine() . " -->";
            }
            return "<!-- TFWire: Component render failed -->";
        }
    }
}

if (!function_exists('turbo_stream')) {
    /**
     * Fluent Turbo Stream builder.
     * 
     * Usage di Controller:
     *   return turbo_stream()
     *       ->remove('user_row_5')
     *       ->success('User berhasil dihapus!')
     *       ->send();
     */
    function turbo_stream(): \TheFramework\App\TFWire\TurboStream
    {
        return new \TheFramework\App\TFWire\TurboStream();
    }
}

if (!function_exists('is_turbo_request')) {
    /**
     * Cek apakah request saat ini adalah Turbo Stream request.
     */
    function is_turbo_request(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'text/vnd.turbo-stream.html');
    }
}

if (!function_exists('is_tfwire_request')) {
    /**
     * Cek apakah request saat ini adalah TFWire component request.
     */
    function is_tfwire_request(): bool
    {
        return \TheFramework\App\TFWire\TFWireEngine::isTFWireRequest();
    }
}

// ==============================================================================
// 🌟 LARAVEL MOCKS (COMPATIBILITY HELPERS) 🌟
// ==============================================================================

if (!function_exists('base_path')) {
    function base_path($path = '') {
        $root = defined('BASE_PATH') ? BASE_PATH : dirname(dirname(dirname(__DIR__)));
        return rtrim($root . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''), DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '') {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('resource_path')) {
    function resource_path($path = '') {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('database_path')) {
    function database_path($path = '') {
        return base_path('database' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('app')) {
    function app($abstract = null, array $parameters = []) {
        $container = \TheFramework\App\Core\Application::getInstance();
        if ($abstract === null) {
            return clone $container;
        }
        try {
            return $container->make($abstract, $parameters);
        } catch (\Throwable $e) {
            // Fallback for missing bindings heavily requested by Vendor Packages
            if (class_exists($abstract)) {
                return new $abstract;
            }
            throw $e;
        }
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null) {
        if (is_null($key)) {
            return \TheFramework\App\Core\Config::all();
        }
        return \TheFramework\App\Core\Config::get($key, $default);
    }
}


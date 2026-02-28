<?php

use TheFramework\Helpers\Helper;
use TheFramework\Helpers\Str;
use TheFramework\Helpers\Arr;
use TheFramework\Helpers\Collection;
use TheFramework\App\Core\Config;

/**
 * Global Helpers - The Framework v5.0 (Paten All-In)
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

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('request')) {
    function request($key = null, $default = null)
    {
        return Helper::request($key, $default);
    }
}

if (!function_exists('session')) {
    function session($key = null, $default = null)
    {
        if ($key === null)
            return $_SESSION;
        if (is_array($key)) {
            foreach ($key as $k => $v)
                $_SESSION[$k] = $v;
            return null;
        }
        return $_SESSION[$key] ?? $default;
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

if (!function_exists('abort')) {
    function abort(int $code, string $message = '')
    {
        http_response_code($code);
        // Bisa diarahkan ke ErrorController atau View khusus
        die("Error $code: $message");
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

if (!function_exists('dd')) {
    /**
     * Dump and Die - Premium Version
     */
    function dd(...$vars)
    {
        if (php_sapi_name() === 'cli') {
            foreach ($vars as $v)
                var_dump($v);
            die();
        }

        echo '<div style="background:#111; color:#0f0; padding:20px; font-family:monospace; font-size:13px; border-left:5px solid #ff4444; margin:10px; border-radius:4px; box-shadow:0 10px 30px rgba(0,0,0,0.5); overflow:auto; max-height:800px;">';
        echo '<div style="color:#666; margin-bottom:10px; font-size:10px;">THE FRAMEWORK v5.0 BUG HUNTER</div>';
        foreach ($vars as $var) {
            echo '<pre style="white-space:pre-wrap;">';
            var_dump($var);
            echo '</pre>';
            echo '<hr style="border:0; border-top:1px solid #333; margin:10px 0;">';
        }
        $trace = debug_backtrace()[0];
        echo '<div style="color:#fff; background:#333; display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px;">Called in: ' . $trace['file'] . ' on line ' . $trace['line'] . '</div>';
        echo '</div>';
        die();
    }
}

if (!function_exists('response')) {
    function response()
    {
        return new class {
            public function json(array $data, int $status = 200)
            {
                return Helper::json($data, $status);
            }
        };
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

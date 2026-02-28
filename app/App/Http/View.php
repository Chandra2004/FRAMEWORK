<?php

namespace TheFramework\App\Http;

use TheFramework\BladeInit;
use Exception;

class View
{
    protected static array $shared = [];

    public static function share(string $key, $value = null)
    {
        if (is_array($key)) {
            self::$shared = array_merge(self::$shared, $key);
        } else {
            self::$shared[$key] = $value;
        }
    }

    public static function render(string $view, $model = [])
    {
        $bladeView = str_replace(['/', '\\'], '.', $view);
        $model = array_merge(self::$shared, $model);

        try {
            $rendered = BladeInit::getInstance()->make($bladeView, $model)->render();
            echo $rendered;
            return;
        } catch (\Throwable $e) {
            // Re-throw to global handler for premium display
            throw $e;
        }

        // Fallback for native PHP views (ONLY if it's a plain .php file, NOT .blade.php)
        $viewPath = str_replace('.', '/', $view);
        $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));

        $fallbackPath = $root . '/resources/views/' . $viewPath . '.php';

        if (file_exists($fallbackPath)) {
            extract(array_merge(self::$shared, $model));
            require $fallbackPath;
            return;
        }

        $errorDetail = "View [{$view}] could not be rendered with Blade and fallback was not found. \n" .
            "Tried fallback path: $fallbackPath";
        throw new Exception($errorDetail);
    }
}

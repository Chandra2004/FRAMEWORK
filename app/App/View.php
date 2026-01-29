<?php

namespace TheFramework\App;

use TheFramework\BladeInit;
use Exception;

class View
{
    public static function render(string $view, $model = [])
    {
        $bladeView = str_replace(['/', '\\'], '.', $view);

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
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2);

        $fallbackPath = $root . '/resources/views/' . $viewPath . '.php';

        if (file_exists($fallbackPath)) {
            extract($model);
            require $fallbackPath;
            return;
        }

        $errorDetail = "View [{$view}] could not be rendered with Blade and fallback was not found. \n" .
            "Tried fallback path: $fallbackPath";
        throw new Exception($errorDetail);
    }
}

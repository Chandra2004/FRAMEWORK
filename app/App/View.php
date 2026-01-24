<?php

namespace TheFramework\App;

use TheFramework\BladeInit;
use Exception;

class View
{
    public static function render(string $view, $model = [])
    {
        $bladeView = str_replace('/', '.', $view);

        try {
            $rendered = BladeInit::getInstance()->make($bladeView, $model)->render();
            echo $rendered;
            return;
        } catch (Exception $e) {
            $errorMsg = "Blade rendering failed for view {$bladeView}: " . $e->getMessage();
            error_log($errorMsg);
            if (Config::get('APP_DEBUG', 'false') === 'true') {
                echo "<div style='color: red; background: #fee; border: 1px solid #f99; padding: 10px; margin: 10px; font-family: sans-serif;'>";
                echo "<strong>Blade Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
                echo "<strong>View:</strong> " . htmlspecialchars($bladeView);
                echo "</div>";
            }
        }

        // Fallback for native PHP views (if Blade fails or is not used)
        $viewPath = str_replace('.', '/', $view);
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2);

        $fallbackPaths = [
            $root . '/resources/views/' . $viewPath . '.blade.php',
            $root . '/resources/views/' . $viewPath . '.php'
        ];

        foreach ($fallbackPaths as $path) {
            if (file_exists($path)) {
                require $path;
                return;
            }
        }

        $errorDetail = "View [{$view}] not found. \nChecked paths:\n - " . implode("\n - ", $fallbackPaths);
        throw new Exception($errorDetail);
    }
}

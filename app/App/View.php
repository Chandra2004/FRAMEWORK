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
            $errorMsg = "Blade rendering failed for view [{$bladeView}]: " . $e->getMessage();
            error_log($errorMsg);

            // Log to framework log file if possible
            try {
                \TheFramework\App\Logging::getLogger()->error($errorMsg, [
                    'view' => $bladeView,
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            } catch (\Throwable $logError) {
            }

            if (\TheFramework\App\Config::get('APP_DEBUG', 'false') === 'true') {
                echo "<div style='color: red; background: #fee; border: 1px solid #f99; padding: 15px; margin: 20px; font-family: sans-serif; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>";
                echo "<h3 style='margin-top: 0;'>Blade Rendering Error</h3>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>View:</strong> " . htmlspecialchars($bladeView) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line: " . $e->getLine() . ")</p>";
                echo "</div>";
            }
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

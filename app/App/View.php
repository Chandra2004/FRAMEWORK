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

        $defaultPath = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';

        if (file_exists($defaultPath)) {
            require $defaultPath;
        } else {
            throw new Exception("View not found: {$view}");
        }
    }
}

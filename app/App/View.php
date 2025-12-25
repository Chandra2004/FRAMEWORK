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
            error_log("Blade rendering failed for view {$bladeView}: " . $e->getMessage());
        }

        $defaultPath = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';

        if (file_exists($defaultPath)) {
            require $defaultPath;
        } else {
            throw new Exception("View not found: {$view}");
        }
    }
}

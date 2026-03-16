<?php

namespace TheFramework\App\Http;

use TheFramework\BladeInit;
use Exception;
use Throwable;

class View
{
    /**
     * Data yang dibagikan ke seluruh view (Global Data).
     */
    protected static array $shared = [];

    /**
     * Bagikan data ke seluruh view (Shared Data).
     * 
     * @param string|array $key
     * @param mixed $value
     */
    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$shared = array_merge(self::$shared, $key);
        } else {
            self::$shared[$key] = $value;
        }
    }

    /**
     * Render view dengan urutan prioritas:
     * 1. Blade Engine (.blade.php)
     * 2. Native PHP Fallback (.php)
     * 
     * @param string $view Nama view (dot notation atau slash)
     * @param array $model Data lokal untuk view
     */
    public static function render(string $view, array $model = [])
    {
        // Standarisasi nama view (dot notation)
        $view = str_replace(['/', '\\'], '.', $view);
        $data = array_merge(self::$shared, $model);

        // 1. Coba menggunakan Blade Engine (Prioritas Utama)
        try {
            $factory = BladeInit::getInstance();
            if ($factory && $factory->exists($view)) {
                echo $factory->make($view, $data)->render();
                return;
            }
        } catch (Throwable $e) {
            // Jika ada error di dalam file Blade, teruskan agar tertangkap global handler
            throw $e;
        }

        // 2. Fallback ke Native PHP views
        $viewPath = str_replace('.', '/', $view);
        $fallbackPath = base_path('resources/views/' . $viewPath . '.php');

        if (file_exists($fallbackPath)) {
            extract($data);
            require $fallbackPath;
            return;
        }

        // 3. Error jika tidak ditemukan sama sekali
        $checkedPaths = [
            "Blade: resources/views/" . $viewPath . ".blade.php",
            "Native: resources/views/" . $viewPath . ".php"
        ];

        $errorDetail = "View [{$view}] not found.\n" . implode("\n", $checkedPaths);
        throw new Exception($errorDetail);
    }

    /**
     * Alias untuk render - Memudahkan pemanggilan sub-view (Include).
     */
    public static function partial(string $view, array $data = [])
    {
        self::render($view, $data);
    }

    /**
     * Memeriksa apakah sebuah view tersedia di filesystem.
     */
    public static function exists(string $view): bool
    {
        $view = str_replace(['/', '\\'], '.', $view);

        // Cek Blade
        try {
            $factory = BladeInit::getInstance();
            if ($factory && $factory->exists($view)) {
                return true;
            }
        } catch (Throwable $e) {
        }

        // Cek Native
        $viewPath = str_replace('.', '/', $view);
        return file_exists(base_path('resources/views/' . $viewPath . '.php'));
    }

    /**
     * Render view dan kembalikan sebagai string HTML (tanpa echo).
     * Sangat berguna untuk dikirim via Email atau dicetak ke PDF.
     * Mendukung Blade (.blade.php) dan Native PHP (.php).
     */
    public static function renderToString(string $view, array $model = []): string|false
    {
        $view = str_replace(['/', '\\'], '.', $view);
        $data = array_merge(self::$shared, $model);

        // 1. Coba menggunakan Blade
        try {
            $factory = BladeInit::getInstance();
            if ($factory && $factory->exists($view)) {
                return $factory->make($view, $data)->render();
            }
        } catch (Throwable $e) {
            // Abaikan jika error / fallback ke native
        }

        // 2. Fallback Native PHP via Output Buffering
        $viewPath = str_replace('.', '/', $view);
        $fallbackPath = base_path('resources/views/' . $viewPath . '.php');

        if (file_exists($fallbackPath)) {
            extract($data);
            ob_start();
            include $fallbackPath;
            return ob_get_clean();
        }

        return false;
    }
}

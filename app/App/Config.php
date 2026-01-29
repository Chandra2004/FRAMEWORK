<?php

namespace TheFramework\App;

use Dotenv\Dotenv;

class Config
{
    private static bool $isLoaded = false;

    public static function loadEnv()
    {
        if (self::$isLoaded) {
            return;
        }

        // 🚀 PERFORMANCE MOD: Config Caching Check
        $storageDir = defined('ROOT_DIR') ? ROOT_DIR . '/storage' : __DIR__ . '/../../storage';
        $cacheFile = $storageDir . '/cache/config.php';

        if (file_exists($cacheFile)) {
            $config = require $cacheFile;
            foreach ($config as $key => $value) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                // Optional: putenv("$key=$value"); 
            }
        } else {
            // Fallback: Parse .env file
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();
        }

        self::$isLoaded = true;
    }

    public static function get($key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}

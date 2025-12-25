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

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        self::$isLoaded = true;
    }

    public static function get($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

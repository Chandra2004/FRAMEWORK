<?php

namespace TheFramework\App;

class Lang
{
    protected static string $locale = 'en';
    protected static string $fallback = 'en';
    protected static array $loaded = [];

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;

        // Handle simple keys like "messages.welcome"
        if (strpos($key, '.') === false) {
            return $key;
        }

        list($file, $lineKey) = explode('.', $key, 2);

        $lines = self::load($file, $locale);
        $value = self::arrayGet($lines, $lineKey);

        // Fallback if not found
        if ($value === null && $locale !== self::$fallback) {
            $fallbackLines = self::load($file, self::$fallback);
            $value = self::arrayGet($fallbackLines, $lineKey);
        }

        // If still not found, return the key itself
        if ($value === null) {
            return $key;
        }

        // Replacements :name -> value
        if (!empty($replace)) {
            foreach ($replace as $placeholder => $val) {
                $value = str_replace(':' . $placeholder, $val, $value);
            }
        }

        return $value;
    }

    protected static function load(string $file, string $locale): array
    {
        $cacheKey = "{$locale}.{$file}";
        if (isset(self::$loaded[$cacheKey])) {
            return self::$loaded[$cacheKey];
        }

        $path = __DIR__ . "/../../resources/lang/{$locale}/{$file}.php";

        if (file_exists($path)) {
            $content = require $path;
            self::$loaded[$cacheKey] = is_array($content) ? $content : [];
        } else {
            self::$loaded[$cacheKey] = [];
        }

        return self::$loaded[$cacheKey];
    }

    protected static function arrayGet(array $array, string $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }

        return $array;
    }
}

<?php

namespace TheFramework\App\Core;

/**
 * Lang — Localization & Translation System
 * 
 * Mendukung dot notation, placeholders, pluralization, JSON translation files,
 * locale detection, dan fitur ekstra.
 * 
 * @package TheFramework\App\Core
 * @version 5.0.1
 */
class Lang
{
    /**
     * Locale aktif
     */
    protected static string $locale = 'en';

    /**
     * Fallback locale
     */
    protected static string $fallback = 'en';

    /**
     * Loaded translation arrays
     * @var array<string, array>
     */
    protected static array $loaded = [];

    /**
     * JSON translations
     * @var array<string, array>
     */
    protected static array $jsonLoaded = [];

    /**
     * Custom lang directory
     */
    protected static ?string $langDir = null;

    /**
     * Missing translation handler
     * @var \Closure|null
     */
    protected static ?\Closure $missingHandler = null;

    /**
     * Tracked missing keys (untuk debugging)
     */
    protected static array $missingKeys = [];

    // ========================================================
    //  CONFIGURATION
    // ========================================================

    /**
     * Set locale aktif
     */
    public static function setLocale(string $locale): void
    {
        static::$locale = $locale;
    }

    /**
     * Get locale aktif
     */
    public static function getLocale(): string
    {
        return static::$locale;
    }

    /**
     * Set fallback locale
     */
    public static function setFallbackLocale(string $locale): void
    {
        static::$fallback = $locale;
    }

    /**
     * Get fallback locale
     */
    public static function getFallbackLocale(): string
    {
        return static::$fallback;
    }

    /**
     * Set custom lang directory
     */
    public static function setLangDir(string $path): void
    {
        static::$langDir = rtrim($path, '/\\');
    }

    /**
     * Get lang directory
     */
    public static function getLangDir(): string
    {
        if (static::$langDir === null) {
            $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
            static::$langDir = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
        }
        return static::$langDir;
    }

    /**
     * Set handler untuk missing translations
     * 
     * Contoh: Lang::handleMissing(fn($key, $locale) => Log::warning("Missing: $key [$locale]"))
     */
    public static function handleMissing(\Closure $handler): void
    {
        static::$missingHandler = $handler;
    }

    // ========================================================
    //  TRANSLATION: GET
    // ========================================================

    /**
     * Ambil terjemahan
     * 
     * @param string $key Dot notation key (file.key atau file.nested.key)
     * @param array $replace Placeholders: ['name' => 'John']
     * @param string|null $locale Override locale
     * @return string
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? static::$locale;

        // Key tanpa dot → coba dari JSON translation
        if (!str_contains($key, '.')) {
            $value = static::getFromJson($key, $locale);
            if ($value !== null) {
                return static::makeReplacements($value, $replace);
            }
            return static::handleMissingKey($key, $locale);
        }

        [$file, $lineKey] = explode('.', $key, 2);

        // Load file PHP
        $lines = static::load($file, $locale);
        $value = static::arrayGet($lines, $lineKey);

        // Fallback
        if ($value === null && $locale !== static::$fallback) {
            $fallbackLines = static::load($file, static::$fallback);
            $value = static::arrayGet($fallbackLines, $lineKey);
        }

        // Coba JSON juga
        if ($value === null) {
            $jsonValue = static::getFromJson($key, $locale);
            if ($jsonValue !== null) {
                return static::makeReplacements($jsonValue, $replace);
            }
        }

        if ($value === null) {
            return static::handleMissingKey($key, $locale);
        }

        // Jika value adalah array (nested), return sebagai JSON
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return static::makeReplacements((string) $value, $replace);
    }

    /**
     * Alias untuk get() — lebih pendek
     */
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return static::get($key, $replace, $locale);
    }

    /**
     * Cek apakah terjemahan ada
     */
    public static function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? static::$locale;

        if (!str_contains($key, '.')) {
            return static::getFromJson($key, $locale) !== null;
        }

        [$file, $lineKey] = explode('.', $key, 2);
        $lines = static::load($file, $locale);
        return static::arrayGet($lines, $lineKey) !== null;
    }

    // ========================================================
    //  PLURALIZATION
    // ========================================================

    /**
     * Ambil terjemahan dengan pluralization
     * 
     * Format translation:
     *   'apples' => '{0} No apples|{1} One apple|[2,*] :count apples'
     *   'items'  => 'one item|:count items'       // Simple (singular|plural)
     * 
     * @param string $key Translation key
     * @param int $count Jumlah item
     * @param array $replace Placeholders (otomatis include :count)
     * @param string|null $locale
     * @return string
     */
    public static function choice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        $line = static::get($key, [], $locale);

        // Jangan proses jika key sama (not found)
        if ($line === $key)
            return $key;

        // Add :count ke replacements
        $replace['count'] = $count;

        $selected = static::choosePluralForm($line, $count);
        return static::makeReplacements($selected, $replace);
    }

    /**
     * Alias
     */
    public static function transChoice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        return static::choice($key, $count, $replace, $locale);
    }

    /**
     * Pilih plural form yang sesuai
     */
    protected static function choosePluralForm(string $line, int $count): string
    {
        $segments = explode('|', $line);

        foreach ($segments as $segment) {
            $segment = trim($segment);

            // Format: {N} text
            if (preg_match('/^\{(\d+)\}\s*(.+)$/', $segment, $matches)) {
                if ((int) $matches[1] === $count) {
                    return trim($matches[2]);
                }
                continue;
            }

            // Format: [min,max] text atau [min,*] text
            if (preg_match('/^\[(\d+),(\d+|\*)\]\s*(.+)$/', $segment, $matches)) {
                $min = (int) $matches[1];
                $max = $matches[2] === '*' ? PHP_INT_MAX : (int) $matches[2];
                if ($count >= $min && $count <= $max) {
                    return trim($matches[3]);
                }
                continue;
            }
        }

        // Simple format: "singular|plural"
        if (count($segments) === 2) {
            return trim($count === 1 ? $segments[0] : $segments[1]);
        }

        // Fallback: return first segment
        return trim($segments[0] ?? $line);
    }

    // ========================================================
    //  JSON TRANSLATIONS
    // ========================================================

    /**
     * Load JSON translation file (resources/lang/{locale}.json)
     */
    protected static function loadJson(string $locale): array
    {
        if (isset(static::$jsonLoaded[$locale])) {
            return static::$jsonLoaded[$locale];
        }

        $path = static::getLangDir() . DIRECTORY_SEPARATOR . $locale . '.json';

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $data = json_decode($content, true);
            static::$jsonLoaded[$locale] = is_array($data) ? $data : [];
        } else {
            static::$jsonLoaded[$locale] = [];
        }

        return static::$jsonLoaded[$locale];
    }

    /**
     * Get dari JSON translation
     */
    protected static function getFromJson(string $key, string $locale): ?string
    {
        $translations = static::loadJson($locale);
        $value = $translations[$key] ?? null;

        // Fallback
        if ($value === null && $locale !== static::$fallback) {
            $fallbackTranslations = static::loadJson(static::$fallback);
            $value = $fallbackTranslations[$key] ?? null;
        }

        return $value;
    }

    // ========================================================
    //  PHP FILE TRANSLATIONS
    // ========================================================

    /**
     * Load PHP translation file
     */
    protected static function load(string $file, string $locale): array
    {
        $cacheKey = "{$locale}.{$file}";
        if (isset(static::$loaded[$cacheKey])) {
            return static::$loaded[$cacheKey];
        }

        $path = static::getLangDir() . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $file . '.php';

        if (file_exists($path)) {
            $content = require $path;
            static::$loaded[$cacheKey] = is_array($content) ? $content : [];
        } else {
            static::$loaded[$cacheKey] = [];
        }

        return static::$loaded[$cacheKey];
    }

    // ========================================================
    //  HELPERS
    // ========================================================

    /**
     * Replace :placeholder dengan value
     */
    protected static function makeReplacements(string $line, array $replace): string
    {
        if (empty($replace))
            return $line;

        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [(string) $value, strtoupper((string) $value), ucfirst((string) $value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Nested array access via dot notation
     */
    protected static function arrayGet(array $array, string $key): mixed
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

    /**
     * Handle missing translation key
     */
    protected static function handleMissingKey(string $key, string $locale): string
    {
        static::$missingKeys[] = ['key' => $key, 'locale' => $locale];

        if (static::$missingHandler) {
            (static::$missingHandler)($key, $locale);
        }

        return $key;
    }

    // ========================================================
    //  LOCALE MANAGEMENT
    // ========================================================

    /**
     * Get daftar locale yang tersedia
     */
    public static function getAvailableLocales(): array
    {
        $langDir = static::getLangDir();
        if (!is_dir($langDir))
            return [];

        $locales = [];

        // Dari folder
        foreach (glob($langDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $dir) {
            $locales[] = basename($dir);
        }

        // Dari JSON files
        foreach (glob($langDir . DIRECTORY_SEPARATOR . '*.json') as $file) {
            $locale = basename($file, '.json');
            if (!in_array($locale, $locales)) {
                $locales[] = $locale;
            }
        }

        sort($locales);
        return $locales;
    }

    /**
     * Cek apakah locale valid (ada file/folder-nya)
     */
    public static function isValidLocale(string $locale): bool
    {
        return in_array($locale, static::getAvailableLocales());
    }

    /**
     * Detect locale dari browser Accept-Language header
     */
    public static function detectFromBrowser(): ?string
    {
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (empty($accept))
            return null;

        $available = static::getAvailableLocales();
        if (empty($available))
            return null;

        // Parse Accept-Language header
        $langs = [];
        foreach (explode(',', $accept) as $part) {
            $parts = explode(';q=', trim($part));
            $lang = strtolower(trim($parts[0]));
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;
            $langs[$lang] = $quality;
        }

        arsort($langs);

        // Match with available locales
        foreach ($langs as $lang => $q) {
            // Exact match
            if (in_array($lang, $available))
                return $lang;

            // Prefix match (en-US → en)
            $prefix = explode('-', $lang)[0];
            if (in_array($prefix, $available))
                return $prefix;
        }

        return null;
    }

    /**
     * Auto-detect dan set locale dari browser
     */
    public static function autoDetect(): string
    {
        $detected = static::detectFromBrowser();
        if ($detected) {
            static::setLocale($detected);
            return $detected;
        }
        return static::$locale;
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Get semua translations untuk file/locale tertentu
     */
    public static function all(string $file, ?string $locale = null): array
    {
        return static::load($file, $locale ?? static::$locale);
    }

    /**
     * Get semua JSON translations
     */
    public static function allJson(?string $locale = null): array
    {
        return static::loadJson($locale ?? static::$locale);
    }

    /**
     * Dapatkan missing keys yang tercatat (untuk debugging/QA)
     */
    public static function getMissingKeys(): array
    {
        return static::$missingKeys;
    }

    /**
     * Clear missing keys
     */
    public static function clearMissingKeys(): void
    {
        static::$missingKeys = [];
    }

    /**
     * Clear loaded translations cache (reload on next access)
     */
    public static function clearCache(): void
    {
        static::$loaded = [];
        static::$jsonLoaded = [];
    }

    /**
     * Temporarily switch locale, jalankan callback, lalu restore
     */
    public static function withLocale(string $locale, \Closure $callback): mixed
    {
        $original = static::$locale;
        static::$locale = $locale;

        try {
            return $callback();
        } finally {
            static::$locale = $original;
        }
    }

    /**
     * Get translation count per locale/file
     */
    public static function stats(?string $locale = null): array
    {
        $locale = $locale ?? static::$locale;
        $langDir = static::getLangDir();
        $localeDir = $langDir . DIRECTORY_SEPARATOR . $locale;
        $result = [];

        if (is_dir($localeDir)) {
            foreach (glob($localeDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
                $name = basename($file, '.php');
                $data = require $file;
                $result[$name] = is_array($data) ? static::countKeysRecursive($data) : 0;
            }
        }

        // JSON
        $jsonFile = $langDir . DIRECTORY_SEPARATOR . $locale . '.json';
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            $result['_json'] = is_array($data) ? count($data) : 0;
        }

        $result['_total'] = array_sum($result);
        return $result;
    }

    /**
     * Count keys recursively in nested array
     */
    protected static function countKeysRecursive(array $array): int
    {
        $count = 0;
        foreach ($array as $value) {
            if (is_array($value)) {
                $count += static::countKeysRecursive($value);
            } else {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Formatted summary
     */
    public static function summary(): string
    {
        $locales = static::getAvailableLocales();
        $lines = [];
        $lines[] = "📊 Localization Summary";
        $lines[] = str_repeat('─', 40);
        $lines[] = "   Active:   " . static::$locale;
        $lines[] = "   Fallback: " . static::$fallback;
        $lines[] = "   Locales:  " . implode(', ', $locales);
        $lines[] = "   Missing:  " . count(static::$missingKeys) . " key(s)";

        return implode("\n", $lines);
    }
}

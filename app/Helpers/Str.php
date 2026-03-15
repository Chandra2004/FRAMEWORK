<?php

namespace TheFramework\Helpers;

/**
 * String Helper - Paten Engine
 * Menyediakan manipulasi string tingkat lanjut melebihi standar Laravel.
 */
class Str
{
    /**
     * Ubah string menjadi slug URL yang bersih.
     */
    public static function slug(string $title, string $separator = '-'): string
    {
        $title = preg_replace('~[^\pL\d]+~u', $separator, $title);
        // Fallback jika iconv gagal/tidak support locale us-ascii di environment tertentu (misal Docker)
        $converted = @iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $title);
        $title = ($converted !== false) ? $converted : $title;
        $title = preg_replace('~[^-\w]+~', '', $title);
        $title = trim($title, $separator);
        $title = preg_replace('~-+~', $separator, $title);
        return strtolower($title);
    }

    /**
     * Generate random string yang aman secara kriptografi.
     */
    public static function random(int $length = 16): string
    {
        return bin2hex(random_bytes(ceil($length / 2)));
    }

    /**
     * Potong string dengan akhiran (Limit characters).
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) return $value;
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Cek apakah string mengandung substring tertentu.
     */
    public static function contains(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) return true;
        }
        return false;
    }

    /**
     * Cek apakah string diawali dengan kata tertentu.
     */
    public static function startsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && mb_strpos($haystack, $needle) === 0) return true;
        }
        return false;
    }

    /**
     * Cek apakah string diakhiri dengan kata tertentu.
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && mb_substr($haystack, -mb_strlen($needle)) === (string) $needle) return true;
        }
        return false;
    }

    /**
     * Ubah string menjadi CamelCase.
     */
    public static function camel(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value))));
    }

    /**
     * Ubah string menjadi snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }
        return $value;
    }

    /**
     * Masking string (misal email: ch***@gmail.com).
     */
    public static function mask(string $string, string $character, int $index, ?int $length = null): string
    {
        if ($character === '') return $string;
        $segment = mb_substr($string, $index, $length, 'UTF-8');
        if ($segment === '') return $string;
        $strlen = mb_strlen($string, 'UTF-8');
        $startIndex = $index < 0 ? max(0, $strlen + $index) : $index;
        $segLen = mb_strlen($segment, 'UTF-8');
        $endIndex = $startIndex + $segLen;
        return mb_substr($string, 0, $startIndex, 'UTF-8') .
               str_repeat($character, $segLen) .
               mb_substr($string, $endIndex, $strlen - $endIndex, 'UTF-8');
    }

    /**
     * Generate a UUID (version 4).
     */
    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

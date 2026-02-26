<?php

namespace TheFramework\Helpers;

/**
 * Array Helper - Paten Engine
 * Manipulasi array tingkat tinggi.
 */
class Arr
{
    /**
     * Cek apakah key ada di dalam array (mendukung dot notation).
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) return true;
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Ambil data dari array menggunakan dot notation.
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (array_key_exists($key, $array)) return $array[$key];
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }

    /**
     * Set data ke array menggunakan dot notation.
     */
    public static function set(array &$array, string $key, $value): array
    {
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * Hapus data dari array menggunakan dot notation.
     */
    public static function forget(array &$array, $keys): void
    {
        $original = &$array;
        $keys = (array) $keys;
        if (count($keys) === 0) return;

        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Ambil sebagian (subset) dari array.
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Kecualikan (exclude) sebagian array.
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Ratakan array multidimensi menjadi satu level (dot notation).
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }
}

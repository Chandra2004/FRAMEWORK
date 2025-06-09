<?php
    namespace {{NAMESPACE}}\App;

    class CacheManager {
        protected static string $cacheDir = __DIR__ . '/../Storage/cache/';

        /**
         * Simpan cache dengan TTL (time-to-live)
         */
        public static function remember($key, $ttl, $callback)
        {
            self::ensureCacheDir();

            $cacheKey = md5(is_array($key) ? json_encode($key) : $key);
            $filePath = self::$cacheDir . $cacheKey . '.cache';

            if (file_exists($filePath)) {
                $data = json_decode(file_get_contents($filePath), true);
                if (isset($data['expires_at']) && time() < $data['expires_at']) {
                    return $data['value'];
                }
            }

            $value = $callback();
            $data = [
                'value' => $value,
                'expires_at' => time() + $ttl
            ];

            file_put_contents($filePath, json_encode($data));
            return $value;
        }

        /**
         * Hapus 1 atau banyak cache berdasarkan key
         */
        public static function forget($key)
        {
            self::ensureCacheDir();

            $keys = is_array($key) ? $key : [$key];
            $success = true;

            foreach ($keys as $k) {
                $cacheKey = md5(is_array($k) ? json_encode($k) : $k);
                $filePath = self::$cacheDir . $cacheKey . '.cache';

                if (file_exists($filePath)) {
                    $success = $success && unlink($filePath);
                }
            }

            return $success;
        }

        /**
         * Pastikan direktori cache tersedia
         */
        protected static function ensureCacheDir()
        {
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
    }
?>
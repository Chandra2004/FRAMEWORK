<?php
    namespace {{NAMESPACE}}\App;

    use {{NAMESPACE}}\App\Config;
    use {{NAMESPACE}}\App\Logging; // Tambahkan ini

    class RateLimiter {
        private static $fallbackDir = __DIR__ . '/../Storage/cache/ratelimit/';
        private static $fallbackLimit = 50; // Lebih ketat dari sebelumnya

        public static function check($key, $limit = 100, $window = 60) {
            try {
                $redis = Config::redis();
                $current = $redis->get($key);

                if ($current === false) {
                    $redis->setex($key, $window, 1);
                    return true;
                }

                if ($current >= $limit) {
                    http_response_code(429);
                    echo json_encode(['error' => 'Too many requests']);
                    exit;
                }

                $redis->incr($key);
                return true;
            } catch (\Exception $e) {
                Logging::getLogger()->error('Rate Limiter Error: ' . $e->getMessage()); // Ganti error_log
                return self::fallbackCheck($key, self::$fallbackLimit, $window);
            }
        }

        private static function fallbackCheck($key, $limit, $window) {
            if (!is_dir(self::$fallbackDir)) {
                mkdir(self::$fallbackDir, 0755, true);
            }

            $file = self::$fallbackDir . md5($key) . '.json';
            $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [
                'count' => 0,
                'timestamp' => time()
            ];

            if (time() - $data['timestamp'] > $window) {
                $data = ['count' => 0, 'timestamp' => time()];
            }

            if ($data['count'] >= $limit) {
                http_response_code(429);
                echo json_encode(['error' => 'Too many requests (fallback)']);
                exit;
            }

            $data['count']++;
            file_put_contents($file, json_encode($data), LOCK_EX);
            return true;
        }
    }
?>
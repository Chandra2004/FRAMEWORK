<?php
    namespace {{NAMESPACE}}\App;

    use Dotenv\Dotenv;
    use Predis\Client;

    class Config {
        private static $redis;

        public static function loadEnv() {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        public static function get($key) {
            return $_ENV[$key] ?? null;
        }

        public static function redis() {
            if (!self::$redis) {
                $options = [
                    'scheme' => self::get('REDIS_SCHEME') ?? 'tcp',
                    'host'   => self::get('REDIS_HOST') ?? '127.0.0.1',
                    'port'   => self::get('REDIS_PORT') ?? 6379,
                    'password' => self::get('REDIS_PASSWORD') ?? null,
                ];

                if (self::get('REDIS_TLS') === 'true') {
                    $options['ssl'] = ['verify_peer' => true];
                }

                self::$redis = new Client($options);
            }
            return self::$redis;
        }
    }
?>
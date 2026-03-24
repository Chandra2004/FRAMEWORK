<?php

namespace TheFramework\App\TFWire\Plugin;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  Rate Limiter Plugin — Protect Against Spam/DDoS             ║
 * ║                                                              ║
 * ║  Usage:                                                      ║
 * ║    PluginManager::register(RateLimiter::class);              ║
 * ║                                                              ║
 * ║  Configuration:                                              ║
 * ║    TFWIRE_RATE_LIMIT=60       (max requests per window)      ║
 * ║    TFWIRE_RATE_WINDOW=60      (window in seconds)            ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class RateLimiter extends TFWirePlugin
{
    public function register(PluginManager $manager): void
    {
        $manager->on('beforeAction', function ($component, $action) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $key = 'tfwire_rl:' . $ip . ':' . get_class($component);
            
            $limit  = (int) ($_ENV['TFWIRE_RATE_LIMIT']  ?? 60);
            $window = (int) ($_ENV['TFWIRE_RATE_WINDOW'] ?? 60);

            // Use APCu if available, otherwise file-based
            if (function_exists('apcu_fetch')) {
                return $this->checkWithApcu($key, $limit, $window);
            }
            
            return $this->checkWithFile($key, $limit, $window);
        }, 1); // Priority 1 = runs first
    }

    private function checkWithApcu(string $key, int $limit, int $window): ?bool
    {
        $current = \apcu_fetch($key);
        if ($current === false) {
            \apcu_store($key, 1, $window);
            return null;
        }
        
        if ($current >= $limit) {
            http_response_code(429);
            return false; // Block action
        }
        
        \apcu_inc($key);
        return null;
    }

    private function checkWithFile(string $key, int $limit, int $window): ?bool
    {
        $dir = sys_get_temp_dir() . '/tfwire_ratelimit';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        
        $file = $dir . '/' . md5($key) . '.json';
        
        $data = ['count' => 0, 'reset' => time() + $window];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            $data = $raw ? json_decode($raw, true) : $data;
        }

        // Reset window if expired
        if (time() > ($data['reset'] ?? 0)) {
            $data = ['count' => 0, 'reset' => time() + $window];
        }

        if ($data['count'] >= $limit) {
            http_response_code(429);
            return false;
        }

        $data['count']++;
        @file_put_contents($file, json_encode($data), LOCK_EX);
        
        return null;
    }
}

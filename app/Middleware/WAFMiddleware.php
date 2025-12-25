<?php

namespace TheFramework\Middleware;

use TheFramework\App\Logging;

class WAFMiddleware implements Middleware
{
    public function before()
    {
        // Regex yang lebih spesifik untuk mengurangi false positive
        // Mencegah blokir kata umum seperti "select", "update" dalam kalimat biasa
        $patterns = [
            'sql_injection' => [
                'pattern' => '/(\b(union\s+select|insert\s+into.+values|delete\s+from|update\s+\w+\s+set|drop\s+table|alter\s+table|truncate\s+table)\b)/i',
                'description' => 'SQL Injection Attempt'
            ],
            'sql_logic' => [
                'pattern' => '/(\b(1\s*=\s*1|1\s*=\s*2)\b|--\s|\/\*.*\*\/)/',
                'description' => 'SQL Logic Injection'
            ],
            'xss' => [
                'pattern' => '/(<script|javascript:|vbscript:|expression\(|on(load|error|click|mouseover|submit|reset|focus|blur)\s*=)/i',
                'description' => 'XSS Attempt'
            ],
            'path_traversal' => [
                'pattern' => '/(\.\.\/|\.\.\\\\)/',
                'description' => 'Path Traversal'
            ],
            'command_injection' => [
                'pattern' => '/(\b(exec|system|shell_exec|passthru|proc_open|popen)\s*\()/i',
                'description' => 'Command Injection'
            ]
        ];

        // Helper untuk scan
        $scan = function ($data, $sourceName) use ($patterns) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // Recursive untuk array input
                    // $scan($value, $sourceName); // Recursive require closure reference optimization, skip for simple
                    continue;
                }

                if (!is_string($value))
                    continue;

                // Allow some keys (optional logic could be added here)

                foreach ($patterns as $type => $rule) {
                    if (preg_match($rule['pattern'], $value)) {
                        Logging::getLogger()->warning("WAF: Blocked $type in $sourceName key='$key'. Value snippet: " . substr($value, 0, 50));

                        if (\TheFramework\App\Config::get('APP_ENV') === 'production') {
                            http_response_code(403);
                            exit; // Silent block in prod
                        } else {
                            http_response_code(403);
                            die(json_encode([
                                'error' => 'Security Alert',
                                'reason' => $rule['description'],
                                'key' => $key
                            ]));
                        }
                    }
                }
            }
        };

        $scan($_GET, 'GET');
        $scan($_POST, 'POST');
        // Scan COOKIE if necessary

        // Scan specific helper to check URI for XSS too
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($patterns as $type => $rule) {
            if ($type === 'xss' && preg_match($rule['pattern'], urldecode($uri))) {
                http_response_code(403);
                exit;
            }
        }
    }
}

<?php

namespace TheFramework\Middleware;

use TheFramework\App\Core\Logging;

class WAFMiddleware implements Middleware
{
    public function before()
    {
        // Regex yang lebih spesifik untuk mengurangi false positive
        $patterns = [
            'sql_injection' => [
                'pattern' => '/(\b(union\s+select|insert\s+into.+values|delete\s+from|update\s+\w+\s+set|drop\s+table|alter\s+table|truncate\s+table)\b)/i',
                'description' => 'SQL Injection Attempt'
            ],
            'sql_logic' => [
                'pattern' => '/(\b(1\s*=\s*1|1\s*=\s*0|1\s*=\s*2)\b|--\s|\/\*.*\*\/|#\s)/',
                'description' => 'SQL Logic Injection'
            ],
            'xss' => [
                'pattern' => '/(<script|javascript:|vbscript:|expression\(|on(load|error|click|mouseover|submit|reset|focus|blur)\s*=)/i',
                'description' => 'XSS Attempt'
            ],
            'path_traversal' => [
                'pattern' => '#\.\.[\\\\/]#',
                'description' => 'Path Traversal'
            ],
            'command_injection' => [
                'pattern' => '/(\b(exec|system|shell_exec|passthru|proc_open|popen|eval|assert)\s*\(|`)/i',
                'description' => 'Command/Code Injection'
            ]
        ];

        // Helper recursive scanning
        $scan = function ($data, $sourceName) use ($patterns, &$scan) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $scan($value, $sourceName . "[$key]");
                    continue;
                }

                if (!is_string($value)) continue;

                foreach ($patterns as $type => $rule) {
                    if (preg_match($rule['pattern'], $value)) {
                        Logging::getLogger()->warning("WAF: Blocked $type in $sourceName key='$key'. Value snippet: " . substr($value, 0, 50));

                        $response = [
                            'error' => 'Security Alert',
                            'reason' => $rule['description'],
                            'key' => $key
                        ];

                        if (config('APP_ENV') === 'production') {
                            abort(403, 'Sistem keamanan kami mendeteksi aktivitas mencurigakan.');
                        } else {
                            json($response, 403);
                        }
                    }
                }
            }
        };

        // Scan All Inputs
        $scan(request()->all(), 'REQUEST');
        
        // Scan specific helper to check URI for XSS too
        $uri = request()->path();
        if (preg_match($patterns['xss']['pattern'], urldecode($uri))) {
            abort(403, 'Akses ditolak: URI mengandung karakter tidak aman.');
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}

<?php
    namespace {{NAMESPACE}}\Middleware;

    use {{NAMESPACE}}\App\Logging;

    class WAFMiddleware implements Middleware {
        public function before() {
            $patterns = [
                'sql_injection' => '/(\b(union|select|insert|delete|update|drop|alter)\b|\b(1=1|--|\/\*)\b)/i',
                'xss' => '/(<script|onerror|onload|javascript:)/i',
                'path_traversal' => '/(\.\.\/|\.\.\\\\)/',
                'command_injection' => '/(\b(exec|system|shell_exec|passthru)\b)/i'
            ];

            // Periksa $_GET dan $_POST untuk semua pola
            $input = array_merge($_GET, $_POST);
            foreach ($input as $key => $value) {
                if (is_string($value)) {
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Suspicious request detected']);
                            Logging::getLogger()->warning("WAF: Blocked request with key=$key, value=$value");
                            exit;
                        }
                    }
                }
            }

            // Periksa $_SERVER hanya untuk pola selain SQL Injection
            foreach ($_SERVER as $key => $value) {
                if (is_string($value)) {
                    foreach ($patterns as $type => $pattern) {
                        if ($type !== 'sql_injection' && preg_match($pattern, $value)) {
                            http_response_code(403);
                            echo json_encode(['error' => 'Suspicious request detected']);
                            Logging::getLogger()->warning("WAF: Blocked request with key=$key, value=$value");
                            exit;
                        }
                    }
                }
            }
        }
    }
?>
<?php

namespace TheFramework\Middleware;

use TheFramework\App\Core\Logging;

class WAFMiddleware implements Middleware
{
    /**
     * Track blocked requests for rate limiting WAF itself
     */
    private static array $wafBlockList = [];
    private static int $maxBlocks = 10;

    public function before()
    {
        if (!\TheFramework\App\Core\Config::get('WAF_ENABLED', true)) {
            return;
        }

        $patterns = [
            'sql_injection' => [
                // Enhanced SQL injection patterns
                'pattern' => '~' . '(\b(union\s+select|insert\s+into|delete\s+from|update\s+\w+\s+set|drop\s+table|alter\s+table|truncate\s+table|load_file|outfile|dumpfile|exec\s*\(|xp_)\b|--|\#|/\*|\*/|0x\d+)' . '~i',
                'description' => 'SQL Injection Attempt',
                'severity' => 'HIGH'
            ],
            'sql_logic' => [
                // Enhanced SQL logic patterns with better detection
                'pattern' => '~' . '(\b(\d+\s*=\s*\d+)|\'\s*=\s*\'|or\s+1\s*=\s*1|and\s+1\s*=\s*1|\btrue\b|\bfalse\b|\bnull\b|;\s*waitfor|;\s*dbms_|having\s+\d+\s*=\s*\d+)' . '~i',
                'description' => 'SQL Logic Injection',
                'severity' => 'HIGH'
            ],
            'xss' => [
                // Enhanced XSS patterns
                'pattern' => '~' . '(<script|javascript:|vbscript:|data:text/html|expression\(|on(load|error|click|mouseover|mouseenter|mouseleave|mousedown|mouseup|submit|reset|focus|blur|change|keydown|keyup|keypress|drop|drag|contextmenu|pointer|wheel)\s*=|alert\(|confirm\(|prompt\(|<\s*iframe|<\s*object|<\s*embed|<\s*base|<\s*link|<\s*meta|<\s*applet|<\s*svg|<\s*math|base64|eval\(|setTimeout\(|setInterval\()' . '~i',
                'description' => 'XSS Attempt',
                'severity' => 'HIGH'
            ],
            'path_traversal' => [
                // Enhanced path traversal
                'pattern' => '~' . '(\.\.[\\\\/]|%2e%2e|etc/passwd|etc/shadow|etc/group|proc/self|boot\.ini|win\.ini|C:\\\\Windows|C:\\\\boot|/etc/|\.\./|\.\.\\\\)' . '~i',
                'description' => 'Path Traversal/LFI',
                'severity' => 'HIGH'
            ],
            'command_injection' => [
                // Enhanced command injection
                'pattern' => '~' . '(\b(exec|system|shell_exec|passthru|proc_open|popen|curl_exec|curl_multi_exec|wget|curl|phpinfo|eval|assert|create_function|preg_replace.*/e|call_user_func|call_user_func_array|array_map|usort|uasort|uksort|var_dump|var_export|print_r|serialize|unserialize)\s*\(|`|\$\(|\$\{|\||\&|\;|\:\;|\|\||\&\&|>\s*/|<\s*\w+)' . '~i',
                'description' => 'Command/Code Injection',
                'severity' => 'CRITICAL'
            ],
            'ldap_injection' => [
                'pattern' => '~' . '(\*\)|\(\||\&\&|\(\!|\(\w+=|%2a\(|#\(|/\*|\*/)' . '~i',
                'description' => 'LDAP Injection',
                'severity' => 'MEDIUM'
            ],
            'xml_injection' => [
                'pattern' => '~' . '(<!DOCTYPE|<!ENTITY|<!ENTITY.*SYSTEM|CDATA|<\?xml|xmlns:xsi|xsi:schemaLocation)' . '~i',
                'description' => 'XML Injection',
                'severity' => 'MEDIUM'
            ],
            'template_injection' => [
                'pattern' => '~' . '(\{\{.*\}\}|\{\%.*\%\}|\{\{.*\|.*\}\}|__tostring__|__invoke__|phpinfo|md5|sha1)' . '~i',
                'description' => 'Template Injection',
                'severity' => 'MEDIUM'
            ]
        ];

        // Enhanced normalization: Strip comments and encoding to prevent bypasses
        $normalize = function ($value) {
            if (!is_string($value)) return $value;
            
            // Decode URL encoding
            $normalized = urldecode($value);
            
            // Decode double encoding
            $normalized = urldecode($normalized);
            
            // Strip SQL comments
            $normalized = preg_replace('#/\*.*?\*/#s', ' ', $normalized);
            $normalized = preg_replace('#--.*$#m', ' ', $normalized);
            
            // Decode hex encoding
            $normalized = preg_replace_callback('/0x[0-9a-fA-F]+/', function($m) {
                return hexdec($m[0]);
            }, $normalized);
            
            // Collapse whitespace
            $normalized = preg_replace('/\s+/', ' ', $normalized);
            
            return $normalized;
        };

        // Get client IP
        $clientIp = self::getClientIpStatic();

        // Helper recursive scanning with IP blocking
        $scan = function ($data, $sourceName) use ($patterns, $normalize, &$scan, $clientIp) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $scan($value, $sourceName . "[$key]");
                    continue;
                }

                if (!is_string($value)) continue;

                $normalizedValue = $normalize($value);

                foreach ($patterns as $type => $rule) {
                    if (preg_match($rule['pattern'], $normalizedValue)) {
                        // Log the attack
                        error_log("WAF DETECTED: $type from $clientIp in $normalizedValue");
                        Logging::getLogger()->warning("WAF: Blocked $type from IP $clientIp in $sourceName key='$key'.");
                        
                        // Track blocked IP
                        self::trackBlockedIp($clientIp, $type);

                        $response = [
                            'success' => false,
                            'error' => 'Security Alert',
                            'reason' => $rule['description'],
                            'blocked' => true
                        ];

                        if (request()->expectsJson()) {
                            json($response, 403);
                        } else {
                            if (\TheFramework\App\Core\Config::get('APP_ENV') === 'production') {
                                abort(403, 'Akses Ditolak: Sistem mendeteksi aktivitas mencurigakan.');
                            } else {
                                json($response, 403);
                            }
                        }
                    }
                }
            }
        };

        $scan(request()->all(), 'REQUEST');
        
        $uri = urldecode(request()->path());
        if (preg_match($patterns['xss']['pattern'], $uri)) {
            abort(403, 'Akses ditolak: URI mengandung karakter tidak aman.');
        }
    }

    public function after()
    {
        // Logic after controller (optional)
    }

    /**
     * Get client IP address with proxy support (static version)
     */
    private static function getClientIpStatic(): string
    {
        $trustedProxy = \TheFramework\App\Core\Config::get('TRUSTED_PROXY', false);
        
        if ($trustedProxy) {
            // Check X-Forwarded-For header
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($ips[0]);
            }
            // Check X-Real-IP header
            if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                return $_SERVER['HTTP_X_REAL_IP'];
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Track blocked IPs and block if too many attempts
     */
    private static function trackBlockedIp(string $ip, string $attackType): void
    {
        $now = time();
        
        // Initialize or update block tracking
        if (!isset(self::$wafBlockList[$ip])) {
            self::$wafBlockList[$ip] = ['count' => 0, 'attacks' => [], 'first_block' => null];
        }
        
        self::$wafBlockList[$ip]['count']++;
        self::$wafBlockList[$ip]['attacks'][] = [
            'type' => $attackType,
            'time' => $now
        ];
        
        // Block IP if too many attacks
        if (self::$wafBlockList[$ip]['count'] >= self::$maxBlocks) {
            if (self::$wafBlockList[$ip]['first_block'] === null) {
                self::$wafBlockList[$ip]['first_block'] = $now;
            }
            
            // Log permanent block
            error_log("WAF: IP $ip blocked permanently due to repeated attacks");
            Logging::getLogger()->warning("WAF: Permanently blocked IP $ip due to repeated $attackType attacks");
            
            // Send 403 Forbidden for blocked IPs
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Access Denied',
                'reason' => 'Your IP has been blocked due to suspicious activity'
            ]);
            exit;
        }
    }
}

<?php

namespace TheFramework\App\Exceptions;

use TheFramework\App\View;
use TheFramework\App\DatabaseException;
use TheFramework\App\Config;

class Handler
{
    public static function register()
    {
        $env = Config::get('APP_ENV', 'production');

        // 1. Error Handler (Warning/Notice)
        set_error_handler(function ($severity, $message, $file, $line) use ($env) {
            if (!(error_reporting() & $severity))
                return;

            if ($env === 'production') {
                error_log("[Warning] $message in $file:$line");
                return;
            }

            $severityName = self::getSeverityName($severity);

            $data = [
                'severity_name' => $severityName,
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'code_snippet' => self::getSnippet($file, $line),
                'error_code' => http_response_code(),
                'request_info' => self::getRequestInfo()
            ];

            if (class_exists(View::class)) {
                View::render('errors.warning', $data);
            } else {
                echo "<b>Warning:</b> $message in $file on line $line";
            }
            exit;
        });

        // 2. Exception Handler (Global)
        set_exception_handler(function ($e) use ($env) {
            $code = $e->getCode();
            $status = ($code >= 400 && $code < 600) ? $code : 500;
            http_response_code($status);

            // A. Database Exception
            if ($e instanceof DatabaseException || str_contains(get_class($e), 'PDOException')) {
                try {
                    View::render('errors.database', [
                        'message' => $e->getMessage(),
                        'env_values' => $_ENV,
                        'request_info' => self::getRequestInfo(),
                        'environment' => [
                            'php_version' => PHP_VERSION,
                            'app_env' => $env
                        ]
                    ]);
                } catch (\Throwable $th) {
                    self::renderCriticalFallback("Database Connection Error", $e->getMessage(), $th->getMessage());
                }
                return;
            }

            // B. Production Error (Generic 500)
            if ($env === 'production') {
                $view = match ($status) {
                    404 => 'errors.404',
                    403 => 'errors.403',
                    default => 'errors.500'
                };
                try {
                    View::render($view);
                } catch (\Throwable $th) {
                    self::renderCriticalFallback("Error $status", "A fatal error occurred on our server. We are looking into it.", $th->getMessage());
                }
                return;
            }

            // C. Local Debug (Exception Blade)
            $trace = [];
            foreach ($e->getTrace() as $t) {
                $trace[] = [
                    'function' => $t['function'] ?? '',
                    'class' => $t['class'] ?? '',
                    'type' => $t['type'] ?? '',
                    'file' => $t['file'] ?? '',
                    'line' => $t['line'] ?? '',
                    'args' => array_map(fn($a) => gettype($a), $t['args'] ?? [])
                ];
            }

            $data = [
                'error_code' => $status,
                'error_code_text' => 'Exception',
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code_snippet' => self::getSnippet($e->getFile(), $e->getLine()),
                'trace_parsed' => $trace,
                'previous' => $e->getPrevious(),
                'request_info' => self::getRequestInfo(true),
                'environment' => [
                    'php_version' => PHP_VERSION,
                    'app_env' => $env,
                    'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
                    'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
                ]
            ];

            try {
                View::render('errors.exception', $data);
            } catch (\Throwable $th) {
                self::renderCriticalFallback("Exception Occurred", $e->getMessage(), $th->getMessage(), $e->getFile(), $e->getLine());
            }
        });

        // 3. Fatal Error (Shutdown Function)
        register_shutdown_function(function () use ($env) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
                if (ob_get_length())
                    ob_end_clean();
                http_response_code(500);

                if ($env === 'production') {
                    View::render('errors.500');
                    return;
                }

                $typeName = self::getSeverityName($error['type']);

                $data = [
                    'error_code' => 500,
                    'type_name' => $typeName,
                    'type' => $error['type'],
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'code_snippet' => self::getSnippet($error['file'], $error['line']),
                    'request_info' => self::getRequestInfo(),
                    'environment' => [
                        'php_version' => PHP_VERSION,
                        'app_env' => $env
                    ]
                ];

                try {
                    View::render('errors.fatal', $data);
                } catch (\Throwable $th) {
                    self::renderCriticalFallback("Fatal System Error", $error['message'], $th->getMessage(), $error['file'], $error['line']);
                }
            }
        });
    }

    /**
     * Renders a premium fallback error page when view rendering fails
     */
    private static function renderCriticalFallback($title, $message, $renderError, $file = null, $line = null)
    {
        $debug = Config::get('APP_DEBUG', 'false') === 'true';
        $style = <<<CSS
        <style>
            body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #0f172a; color: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .card { background: #1e293b; border: 1px solid #334155; padding: 40px; border-radius: 16px; max-width: 600px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
            .icon { width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; font-size: 32px; font-weight: bold; }
            h1 { font-size: 24px; margin: 0 0 12px 0; color: #ffffff; }
            p { color: #94a3b8; line-height: 1.6; margin: 0 0 24px 0; font-size: 16px; }
            .debug-box { background: #020617; border: 1px solid #1e293b; padding: 16px; border-radius: 8px; font-size: 13px; font-family: 'Fira Code', monospace; overflow-x: auto; color: #38bdf8; }
            .debug-label { color: #64748b; margin-bottom: 4px; display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
            .path { color: #94a3b8; word-break: break-all; }
            .footer { margin-top: 24px; font-size: 12px; color: #475569; text-align: center; }
        </style>
CSS;
        echo $style;
        echo '<div class="card">';
        echo '<div class="icon">!</div>';
        echo '<h1>' . htmlspecialchars($title) . '</h1>';
        echo '<p>' . htmlspecialchars($message) . '</p>';

        if ($debug) {
            echo '<div>';
            if ($file) {
                echo '<span class="debug-label">Location</span>';
                echo '<div class="debug-box" style="margin-bottom: 12px;">' . htmlspecialchars($file) . ':' . $line . '</div>';
            }
            echo '<span class="debug-label">Diagnostic Context (View Fallback Active)</span>';
            echo '<div class="debug-box">' . htmlspecialchars($renderError) . '</div>';
            echo '<p style="font-size: 12px; margin-top: 8px; color: #ef4444;">⚠️ This interface is active because the primary design system (Blade) could not find its views on the server.</p>';
            echo '</div>';
        }

        echo '<div class="footer">TheFramework Runtime Support Engine</div>';
        echo '</div>';
    }

    private static function getSnippet($file, $line, $linesAround = 10)
    {
        if (!file_exists($file) || !is_readable($file))
            return [];
        $lines = file($file);
        $start = max(0, $line - $linesAround - 1);
        $end = min(count($lines), $line + $linesAround);
        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = $lines[$i];
        }
        return $snippet;
    }

    private static function getSeverityName($severity)
    {
        return match ($severity) {
            E_WARNING => 'E_WARNING',
            E_NOTICE => 'E_NOTICE',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_CORE_ERROR => 'E_CORE_ERROR',
            default => 'UNKNOWN_ERROR'
        };
    }

    private static function getRequestInfo($includeQuery = false)
    {
        $info = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
        ];

        if ($includeQuery) {
            $info['query'] = $_GET;
        }

        return $info;
    }
}

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

        // 1. Error Handler (Warnings, Notices, etc.)
        set_error_handler(function ($severity, $message, $file, $line) use ($env) {
            if (!(error_reporting() & $severity))
                return;

            if ($env === 'production') {
                error_log("[Warning] $message in $file:$line");
                return;
            }

            if (ob_get_length())
                ob_clean();
            self::handleError($severity, $message, $file, $line, $env);
            exit;
        });

        // 2. Exception Handler (Global)
        set_exception_handler(function ($e) use ($env) {
            $code = $e->getCode();
            $status = ($code >= 400 && $code < 600) ? $code : 500;
            http_response_code($status);

            if ($e instanceof DatabaseException || str_contains(get_class($e), 'PDOException')) {
                self::renderDatabaseError($e, $env);
                return;
            }

            if ($env === 'production') {
                self::renderProductionError($status);
                return;
            }

            self::handleException($e, $env);
        });

        // 3. Fatal Error (Shutdown)
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

                self::handleError($error['type'], $error['message'], $error['file'], $error['line'], $env, true);
            }
        });
    }

    /**
     * Unified Error Handling Logic
     */
    private static function handleError($severity, $message, $file, $line, $env, $isFatal = false)
    {
        $originalFile = $file;
        $isBlade = false;

        // Blade Detection & Mapping (Cross-platform)
        $normalizedFile = str_replace('\\', '/', $file);
        if (str_contains($normalizedFile, 'storage/framework/views') || str_contains($normalizedFile, '.blade.php')) {
            $isBlade = true;
            if (str_contains($normalizedFile, 'storage/framework/views')) {
                $compiledContent = @file_get_contents($file);
                if ($compiledContent && preg_match('/\/\*\*PATH (.*?) ENDPATH\*\*\//', $compiledContent, $matches)) {
                    $originalBladeFile = $matches[1];

                    // Attempt to sync line number (Find where the variable is in Blade)
                    if (str_contains($message, 'Undefined variable $')) {
                        preg_match('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $message, $varMatch);
                        if (isset($varMatch[1])) {
                            $varName = $varMatch[1];
                            $originalLines = explode("\n", @file_get_contents($originalBladeFile));
                            for ($i = max(0, $line - 15); $i < min(count($originalLines), $line + 15); $i++) {
                                if (str_contains($originalLines[$i], '$' . $varName)) {
                                    $line = $i + 1;
                                    break;
                                }
                            }
                        }
                    }
                    $file = $originalBladeFile;
                }
            }
        }

        $severityName = self::getSeverityName($severity);

        $data = [
            'class' => $severityName,
            'error_code_text' => $isFatal ? 'Fatal Error' : 'System Warning',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'code_snippet' => self::getSnippet($file, $line),
            'trace_parsed' => [], // Traditional error handler doesn't have trace easily
            'request_info' => self::getRequestInfo(true),
            'environment' => self::getEnvInfo($env),
            'error_code' => 500
        ];

        $view = $isBlade ? 'errors.viewfails' : ($isFatal ? 'errors.fatal' : 'errors.warning');

        try {
            View::render($view, $data);
        } catch (\Throwable $e) {
            self::renderCriticalFallback("Critical Handler Error", $message, $e->getMessage(), $file, $line);
        }
    }

    /**
     * Unified Exception Handling Logic
     */
    private static function handleException($e, $env)
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $isBlade = false;

        // Blade Detection & Mapping (Cross-platform)
        $normalizedFile = str_replace('\\', '/', $file);
        if (str_contains($normalizedFile, 'storage/framework/views') || str_contains($normalizedFile, '.blade.php')) {
            $isBlade = true;
            if (str_contains($normalizedFile, 'storage/framework/views')) {
                $compiledContent = @file_get_contents($file);
                if ($compiledContent && preg_match('/\/\*\*PATH (.*?) ENDPATH\*\*\//', $compiledContent, $matches)) {
                    $originalBladeFile = $matches[1];

                    // Sync line number for variables in exceptions too
                    $originalLines = explode("\n", @file_get_contents($originalBladeFile));
                    for ($i = max(0, $line - 15); $i < min(count($originalLines), $line + 15); $i++) {
                        if (str_contains($originalLines[$i], '{{') || str_contains($originalLines[$i], '@')) {
                            // Use reported line as fallback if no clear match, otherwise keep it
                        }
                    }
                    $file = $originalBladeFile;
                }
            }
        }

        $trace = [];
        foreach ($e->getTrace() as $t) {
            $tFile = $t['file'] ?? '';
            $isApp = !empty($tFile) && !str_contains($tFile, 'vendor');
            $trace[] = [
                'function' => $t['function'] ?? '',
                'class' => $t['class'] ?? '',
                'type' => $t['type'] ?? '',
                'file' => $tFile,
                'line' => $t['line'] ?? '',
                'is_app' => $isApp,
                'snippet' => ($isApp && !empty($tFile)) ? self::getSnippet($tFile, $t['line'] ?? 0, 3) : [],
                'args' => array_map(function ($a) {
                    if (is_object($a))
                        return get_class($a);
                    if (is_array($a))
                        return 'Array(' . count($a) . ')';
                    if (is_string($a))
                        return '"' . (strlen($a) > 20 ? substr($a, 0, 20) . '...' : $a) . '"';
                    return (string) $a;
                }, $t['args'] ?? [])
            ];
        }

        $data = [
            'class' => get_class($e),
            'error_code_text' => 'Exception',
            'message' => $e->getMessage(),
            'file' => $file,
            'line' => $line,
            'code_snippet' => self::getSnippet($file, $line),
            'trace_parsed' => $trace,
            'request_info' => self::getRequestInfo(true),
            'environment' => self::getEnvInfo($env),
            'error_code' => http_response_code()
        ];

        $view = $isBlade ? 'errors.viewfails' : 'errors.exception';

        try {
            if (ob_get_length())
                ob_clean();
            View::render($view, $data);
        } catch (\Throwable $th) {
            self::renderCriticalFallback("Exception Handler Failed", $e->getMessage(), $th->getMessage(), $file, $line);
        }
    }

    private static function renderDatabaseError($e, $env)
    {
        try {
            View::render('errors.database', [
                'message' => $e->getMessage(),
                'env_values' => $_ENV,
                'request_info' => self::getRequestInfo(),
                'environment' => ['php_version' => PHP_VERSION, 'app_env' => $env]
            ]);
        } catch (\Throwable $th) {
            self::renderCriticalFallback("Database Error", $e->getMessage(), $th->getMessage());
        }
    }

    private static function renderProductionError($status)
    {
        $view = match ($status) {
            404 => 'errors.404',
            403 => 'errors.403',
            default => 'errors.500'
        };
        try {
            View::render($view);
        } catch (\Throwable $th) {
            self::renderCriticalFallback("Error $status", "A fatal error occurred.", $th->getMessage());
        }
    }

    private static function getEnvInfo($env)
    {
        return [
            'php_version' => PHP_VERSION,
            'app_env' => $env,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
        ];
    }

    private static function renderCriticalFallback($title, $message, $renderError, $file = null, $line = null)
    {
        $debug = Config::get('APP_DEBUG', 'false') === 'true';
        echo "<style>body{font-family:sans-serif;background:#0f172a;color:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;box-sizing:border-box;}.card{background:#1e293b;padding:40px;border-radius:16px;max-width:600px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);}h1{font-size:24px;margin:0 0 12px 0;}.debug-box{background:#020617;padding:16px;border-radius:8px;font-size:13px;font-family:monospace;color:#38bdf8;margin-top:12px;}</style>";
        echo "<div class='card'><h1>" . htmlspecialchars($title) . "</h1><p>" . htmlspecialchars($message) . "</p>";
        if ($debug) {
            if ($file)
                echo "<div class='debug-box'>Location: " . htmlspecialchars($file) . ":" . $line . "</div>";
            echo "<div class='debug-box'>Render Error: " . htmlspecialchars($renderError) . "</div>";
        }
        echo "</div>";
    }

    private static function getSnippet($file, $line, $linesAround = 10)
    {
        if (!file_exists($file) || !is_readable($file))
            return [];
        $lines = @file($file);
        if (!$lines)
            return [];
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
            default => 'FATAL_ERROR'
        };
    }

    private static function getRequestInfo($includeQuery = false)
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'query' => $includeQuery ? $_GET : []
        ];
    }
}

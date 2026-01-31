<?php

namespace TheFramework\Http\Controllers\Services;

use TheFramework\App\{Config, Database, View, CacheManager, DatabaseException};
use TheFramework\Helpers\Helper;
use Exception;

class DebugController
{
    /**
     * Parse stack trace menjadi array yang lebih readable
     */
    private static function parseStackTrace(\Throwable $e): array
    {
        $trace = [];
        $traceString = $e->getTraceAsString();
        $traceArray = $e->getTrace();

        foreach ($traceArray as $index => $item) {
            $trace[] = [
                'index' => $index,
                'file' => $item['file'] ?? 'internal',
                'line' => $item['line'] ?? 0,
                'function' => $item['function'] ?? 'unknown',
                'class' => $item['class'] ?? null,
                'type' => $item['type'] ?? '',
                'args' => self::formatTraceArgs($item['args'] ?? []),
            ];
        }

        return $trace;
    }

    /**
     * Format trace arguments untuk ditampilkan
     */
    private static function formatTraceArgs(array $args): array
    {
        $formatted = [];
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $formatted[] = get_class($arg) . ' object';
            } elseif (is_array($arg)) {
                $formatted[] = 'Array(' . count($arg) . ')';
            } elseif (is_string($arg)) {
                $formatted[] = '"' . (strlen($arg) > 50 ? substr($arg, 0, 50) . '...' : $arg) . '"';
            } elseif (is_null($arg)) {
                $formatted[] = 'null';
            } else {
                $formatted[] = (string) $arg;
            }
        }
        return $formatted;
    }

    /**
     * Ambil code snippet dengan context
     */
    private static function getCodeSnippet(string $file, int $line, int $context = 10): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $lines = file($file);
        $start = max(0, $line - $context - 1);
        $end = min(count($lines), $line + $context);

        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = $lines[$i];
        }

        return $snippet;
    }

    /**
     * Ambil request information
     */
    private static function getRequestInfo(): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'query' => $_GET ?? [],
            'post' => $_POST ?? [],
            'headers' => getallheaders() ?: [],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
        ];
    }

    /**
     * Tentukan HTTP error code dari exception
     */
    private static function getErrorCode(\Throwable $e): int
    {
        // Jika exception memiliki method getCode() dan return HTTP status code
        if (method_exists($e, 'getCode') && $e->getCode() >= 400 && $e->getCode() < 600) {
            return $e->getCode();
        }

        // Default berdasarkan tipe exception
        if ($e instanceof \PDOException) {
            return 500;
        }

        if ($e instanceof \InvalidArgumentException) {
            return 400;
        }

        if ($e instanceof \RuntimeException) {
            return 500;
        }

        return 500;
    }

    public static function showException(\Throwable $e, int $httpCode = null)
    {
        if (ob_get_length())
            ob_end_clean();

        // Handle DatabaseException khusus
        if ($e instanceof DatabaseException) {
            self::showDatabaseError($e);
            return;
        }

        $errorCode = $httpCode ?? self::getErrorCode($e);
        http_response_code($errorCode);

        $file = $e->getFile();
        $line = $e->getLine();

        // Handle cached view files
        $originalFile = $file;
        if (strpos($file, 'app\Storage\cache\views') !== false && file_exists($file)) {
            $content = file_get_contents($file);
            if (preg_match('/\/\*\*PATH\s+(.+?)\s+ENDPATH\*\*\//', $content, $matches)) {
                $originalFile = $matches[1];
            }
        }

        View::render('debug.exception', [
            'error_code' => $errorCode,
            'error_code_text' => self::getHttpStatusText($errorCode),
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $originalFile,
            'line' => $line,
            'trace' => $e->getTraceAsString(),
            'trace_parsed' => self::parseStackTrace($e),
            'code_snippet' => self::getCodeSnippet($originalFile, $line),
            'previous' => $e->getPrevious(),
            'request_info' => self::getRequestInfo(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'app_env' => Config::get('APP_ENV', 'unknown'),
                'app_debug' => Config::get('APP_DEBUG', 'false'),
                'memory_usage' => self::formatBytes(memory_get_usage(true)),
                'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            ],
        ]);
        exit;
    }

    public static function showFatal(array $error)
    {
        if (ob_get_length())
            ob_end_clean();

        $errorCode = 500;
        http_response_code($errorCode);

        $file = $error['file'] ?? 'unknown';
        $line = $error['line'] ?? 0;
        $message = $error['message'] ?? 'Fatal error';
        $type = $error['type'] ?? E_ERROR;

        // Handle cached view files
        $originalFile = $file;
        if (strpos($file, 'app\Storage\cache\views') !== false && file_exists($file)) {
            $content = file_get_contents($file);
            if (preg_match('/\/\*\*PATH\s+(.+?)\s+ENDPATH\*\*\//', $content, $matches)) {
                $originalFile = $matches[1];
            }
        }

        View::render('debug.fatal', [
            'error_code' => $errorCode,
            'error_code_text' => self::getHttpStatusText($errorCode),
            'message' => $message,
            'file' => $originalFile,
            'line' => $line,
            'type' => $type,
            'type_name' => self::getErrorTypeName($type),
            'code_snippet' => self::getCodeSnippet($originalFile, $line),
            'request_info' => self::getRequestInfo(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'app_env' => Config::get('APP_ENV', 'unknown'),
                'app_debug' => Config::get('APP_DEBUG', 'false'),
                'memory_usage' => self::formatBytes(memory_get_usage(true)),
                'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            ],
        ]);
        exit;
    }

    public static function showWarning(array $error)
    {
        if (ob_get_length())
            ob_end_clean();

        // Warning biasanya tidak fatal, tapi tetap tampilkan dengan code 200 atau 500 tergantung severity
        $severity = $error['severity'] ?? E_WARNING;
        $errorCode = in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR]) ? 500 : 200;
        http_response_code($errorCode);

        $file = $error['file'] ?? 'unknown';
        $originalFile = $file;
        if (strpos($file, 'app\Storage\cache\views') !== false && file_exists($file)) {
            $content = file_get_contents($file);
            if (preg_match('/\/\*\*PATH\s+(.+?)\s+ENDPATH\*\*\//', $content, $matches)) {
                $originalFile = $matches[1];
            }
        }

        $line = $error['line'] ?? 0;

        View::render('debug.warning', [
            'error_code' => $errorCode,
            'error_code_text' => self::getHttpStatusText($errorCode),
            'message' => $error['message'] ?? 'Unknown warning',
            'file' => $originalFile,
            'line' => $line,
            'severity' => $severity,
            'severity_name' => self::getErrorTypeName($severity),
            'code_snippet' => self::getCodeSnippet($originalFile, $line),
            'request_info' => self::getRequestInfo(),
        ]);
        exit;
    }

    /**
     * Get HTTP status text
     */
    private static function getHttpStatusText(int $code): string
    {
        $statusTexts = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        return $statusTexts[$code] ?? 'Unknown';
    }

    /**
     * Get error type name
     */
    private static function getErrorTypeName(int $type): string
    {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        ];



        return $types[$type] ?? 'UNKNOWN';
    }

    /**
     * Format bytes to human readable
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Show database connection error dengan detail
     */
    private static function showDatabaseError(DatabaseException $e): void
    {
        http_response_code(500);

        // Ambil nilai dari .env untuk ditampilkan
        Config::loadEnv();
        $env_values = [
            'DB_HOST' => Config::get('DB_HOST', 'not set'),
            'DB_PORT' => Config::get('DB_PORT', 'not set'),
            'DB_NAME' => Config::get('DB_NAME', 'not set'),
            'DB_USER' => Config::get('DB_USER', 'not set'),
            'DB_PASS' => Config::get('DB_PASS', 'not set') ? '***hidden***' : 'not set',
        ];

        View::render('Internal::errors.database', [
            'message' => $e->getMessage(),
            'config_errors' => $e->getConfigErrors(),
            'env_errors' => $e->getEnvErrors(),
            'is_required' => $e->isConnectionRequired(),
            'detailed_message' => $e->getDetailedMessage(),
            'env_values' => $env_values,
            'request_info' => self::getRequestInfo(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'app_env' => Config::get('APP_ENV', 'unknown'),
                'app_debug' => Config::get('APP_DEBUG', 'false'),
                'memory_usage' => self::formatBytes(memory_get_usage(true)),
                'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            ],
        ]);
        exit;
    }
}

<?php

namespace TheFramework\App\Internal\Controllers;

use TheFramework\App\Core\Config;
use TheFramework\App\Database\Database;
use TheFramework\App\Http\View;
use TheFramework\App\Database\DatabaseException;
use TheFramework\Helpers\Helper;
use Throwable;

/**
 * DebugController - Framework Internal
 * Engine dibalik tampilan Exception & Fatal Error UI yang premium.
 */
class DebugController
{
    /**
     * Parse stack trace menjadi array yang lebih readable
     */
    private static function parseStackTrace(Throwable $e): array
    {
        $trace = [];
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
    private static function getErrorCode(Throwable $e): int
    {
        if (method_exists($e, 'getCode') && $e->getCode() >= 400 && $e->getCode() < 600) {
            return $e->getCode();
        }

        return 500;
    }

    public static function showException(Throwable $e, int $httpCode = null)
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

        View::render('Internal::errors.exception', [
            'error_code' => $errorCode,
            'error_code_text' => self::getHttpStatusText($errorCode),
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $file,
            'line' => $line,
            'trace' => $e->getTraceAsString(),
            'trace_parsed' => self::parseStackTrace($e),
            'code_snippet' => self::getCodeSnippet($file, $line),
            'request_info' => self::getRequestInfo(),
            'environment' => self::getEnvironmentInfo(),
            'previous_chain' => [],
            'exception_context' => [],
        ]);
        exit;
    }

    private static function getHttpStatusText(int $code): string
    {
        $statusTexts = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        return $statusTexts[$code] ?? 'Unknown';
    }

    private static function getEnvironmentInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'app_env' => Config::get('APP_ENV', 'unknown'),
            'memory_usage' => self::formatBytes(memory_get_usage(true)),
            'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
        ];
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private static function showDatabaseError(DatabaseException $e): void
    {
        http_response_code(500);
        Config::loadEnv();

        View::render('Internal::errors.database', [
            'message' => $e->getMessage(),
            'config_errors' => $e->getConfigErrors(),
            'env_errors' => $e->getEnvErrors(),
            'is_required' => $e->isConnectionRequired(),
            'env_values' => [
                'DB_HOST' => Config::get('DB_HOST'),
                'DB_PORT' => Config::get('DB_PORT'),
                'DB_NAME' => Config::get('DB_NAME'),
            ],
            'request_info' => self::getRequestInfo(),
        ]);
        exit;
    }
}

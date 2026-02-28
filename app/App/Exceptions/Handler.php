<?php

namespace TheFramework\App\Exceptions;

use TheFramework\App\Http\View;
use TheFramework\App\Database\DatabaseException;
use TheFramework\App\Core\Config;

class Handler
{
    // ========================================================
    //  CONFIGURATION
    // ========================================================

    /**
     * Exception classes that should NOT be reported/logged
     */
    protected static array $dontReport = [];

    /**
     * Input fields that are never flashed to session on validation error
     */
    protected static array $dontFlash = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'credit_card',
        'cvv',
    ];

    /**
     * Custom renderers: [ExceptionClass => callable($exception, $request)]
     */
    protected static array $renderers = [];

    /**
     * Custom reporters: [ExceptionClass => callable($exception)]
     */
    protected static array $reporters = [];

    /**
     * External report hooks (Sentry, Bugsnag, etc.)
     */
    protected static array $reportHooks = [];

    /**
     * Track reported exceptions for deduplication
     */
    protected static array $reportedFingerprints = [];

    /**
     * Max identical errors to log per request (rate limiting)
     */
    protected static int $maxReportsPerFingerprint = 3;

    /**
     * Log file path
     */
    protected static ?string $logFile = null;

    // ========================================================
    //  REGISTRATION
    // ========================================================

    public static function register(): void
    {
        $env = Config::get('APP_ENV', 'production');

        // Set log file path
        self::$logFile = self::resolveLogPath();

        // 1. Error Handler (Warnings, Notices, etc.)
        set_error_handler(function ($severity, $message, $file, $line) use ($env) {
            if (!(error_reporting() & $severity))
                return;

            if ($env === 'production') {
                self::reportError($severity, $message, $file, $line);
                return;
            }

            if (ob_get_length())
                ob_clean();
            self::handleError($severity, $message, $file, $line, $env);
            exit;
        });

        // 2. Exception Handler (Global)
        set_exception_handler(function ($e) use ($env) {
            self::handleUncaughtException($e, $env);
        });

        // 3. Fatal Error (Shutdown)
        register_shutdown_function(function () use ($env) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
                if (ob_get_length())
                    ob_end_clean();
                http_response_code(500);

                self::reportError($error['type'], $error['message'], $error['file'], $error['line']);

                if ($env === 'production') {
                    View::render('Internal::errors.500');
                    return;
                }

                self::handleError($error['type'], $error['message'], $error['file'], $error['line'], $env, true);
            }
        });
    }

    // ========================================================
    //  MAIN EXCEPTION HANDLER
    // ========================================================

    protected static function handleUncaughtException(\Throwable $e, string $env): void
    {
        // Determine status code
        $status = self::getStatusCode($e);
        http_response_code($status);

        // Set headers (from HttpException)
        if ($e instanceof HttpException) {
            foreach ($e->getHeaders() as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        // Report the exception
        self::report($e);

        // Check for custom renderer first
        if (self::tryCustomRenderer($e)) {
            return;
        }

        // Check if request expects JSON
        if (self::expectsJson()) {
            self::renderJsonError($e, $status, $env);
            return;
        }

        // Production mode
        if ($env === 'production') {
            self::renderProductionError($status);
            return;
        }

        // Database errors
        if ($e instanceof DatabaseException || $e instanceof \PDOException) {
            self::renderDatabaseError($e, $env);
            return;
        }

        // Validation errors — redirect back with errors
        if ($e instanceof ValidationException) {
            self::renderValidationError($e);
            return;
        }

        // Authentication errors — redirect to login
        if ($e instanceof AuthenticationException) {
            self::renderAuthenticationError($e);
            return;
        }

        // General exceptions
        self::handleException($e, $env);
    }

    // ========================================================
    //  REPORTING / LOGGING
    // ========================================================

    /**
     * Report/log an exception
     */
    public static function report(\Throwable $e): void
    {
        // Check dontReport list
        foreach (self::$dontReport as $type) {
            if ($e instanceof $type) {
                return;
            }
        }

        // Check if exception itself says not to report
        if (method_exists($e, 'report')) {
            if (call_user_func([$e, 'report']) === false) {
                return;
            }
        }

        // Fingerprinting — avoid duplicate reports
        $fingerprint = self::fingerprint($e);
        $count = self::$reportedFingerprints[$fingerprint] ?? 0;
        if ($count >= self::$maxReportsPerFingerprint) {
            return;
        }
        self::$reportedFingerprints[$fingerprint] = $count + 1;

        // Build log context
        $context = self::buildLogContext($e);

        // Check custom reporters
        foreach (self::$reporters as $type => $callback) {
            if ($e instanceof $type) {
                $callback($e, $context);
                return;
            }
        }

        // Fire external hooks (Sentry, Bugsnag, etc.)
        foreach (self::$reportHooks as $hook) {
            try {
                $hook($e, $context);
            } catch (\Throwable) {
                // Don't let report hooks break the reporter
            }
        }

        // Default: write to log file
        self::writeToLog($e, $context);
    }

    /**
     * Report a non-exception error
     */
    protected static function reportError(int $severity, string $message, string $file, int $line): void
    {
        $severityName = self::getSeverityName($severity);
        $logMessage = "[{$severityName}] {$message} in {$file}:{$line}";
        self::writeRawLog('ERROR', $logMessage);
    }

    /**
     * Write an exception to the log file
     */
    protected static function writeToLog(\Throwable $e, array $context): void
    {
        $severity = self::getLogLevel($e);
        $class = get_class($e);

        $logMessage = "[{$class}] {$e->getMessage()}";
        $logMessage .= " in {$e->getFile()}:{$e->getLine()}";

        if (!empty($context['previous'])) {
            $logMessage .= " | Previous: {$context['previous']}";
        }

        self::writeRawLog($severity, $logMessage);
    }

    /**
     * Core log writer
     */
    protected static function writeRawLog(string $level, string $message): void
    {
        if (!self::$logFile) {
            self::$logFile = self::resolveLogPath();
        }

        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        @file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Resolve log file path — daily rotation
     */
    protected static function resolveLogPath(): string
    {
        $basePath = Config::get('BASE_PATH', dirname(__DIR__, 3));
        $date = date('Y-m-d');
        return $basePath . "/storage/logs/framework-{$date}.log";
    }

    /**
     * Generate a unique fingerprint for an exception (deduplication)
     */
    protected static function fingerprint(\Throwable $e): string
    {
        return md5(get_class($e) . '|' . $e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
    }

    /**
     * Build log context from exception
     */
    protected static function buildLogContext(\Throwable $e): array
    {
        $context = [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'memory' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        ];

        // Exception context() method support
        if (method_exists($e, 'context')) {
            $context['extra'] = call_user_func([$e, 'context']);
        }

        // Previous exception chain
        if ($e->getPrevious()) {
            $prev = $e->getPrevious();
            $context['previous'] = get_class($prev) . ': ' . $prev->getMessage() . ' in ' . $prev->getFile() . ':' . $prev->getLine();
        }

        return $context;
    }

    /**
     * Get log level based on exception type
     */
    protected static function getLogLevel(\Throwable $e): string
    {
        if ($e instanceof HttpException) {
            return match (true) {
                $e->getStatusCode() >= 500 => 'CRITICAL',
                $e->getStatusCode() >= 400 => 'WARNING',
                default => 'INFO',
            };
        }

        if ($e instanceof ValidationException)
            return 'NOTICE';
        if ($e instanceof ModelNotFoundException)
            return 'WARNING';
        if ($e instanceof AuthenticationException)
            return 'WARNING';
        if ($e instanceof AuthorizationException)
            return 'WARNING';
        if ($e instanceof DatabaseException || $e instanceof \PDOException)
            return 'CRITICAL';

        return 'ERROR';
    }

    // ========================================================
    //  STATUS CODE RESOLUTION
    // ========================================================

    /**
     * Get HTTP status code from exception
     */
    protected static function getStatusCode(\Throwable $e): int
    {
        // Exceptions with getStatusCode()
        if (method_exists($e, 'getStatusCode')) {
            return (int) call_user_func([$e, 'getStatusCode']);
        }

        // Known exception → status mappings
        if ($e instanceof ModelNotFoundException)
            return 404;
        if ($e instanceof AuthenticationException)
            return 401;
        if ($e instanceof AuthorizationException)
            return 403;
        if ($e instanceof ValidationException)
            return 422;

        // Integer HTTP codes from exception code
        $code = $e->getCode();
        if (is_int($code) && $code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    // ========================================================
    //  JSON / API ERROR RESPONSES
    // ========================================================

    /**
     * Check if request expects JSON response
     */
    protected static function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $xRequested = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($accept, 'text/json')
            || str_contains($contentType, 'application/json')
            || strtolower($xRequested) === 'xmlhttprequest'
            || str_starts_with(ltrim($_SERVER['REQUEST_URI'] ?? '', '/'), 'api/');
    }

    /**
     * Render error as JSON
     */
    protected static function renderJsonError(\Throwable $e, int $status, string $env): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $response = ['message' => $e->getMessage()];

        // Validation errors include field errors
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        // Debug mode: include trace info
        if ($env !== 'production') {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();

            if ($e instanceof DatabaseException && $e->getSql()) {
                $response['sql'] = $e->getSql();
            }

            $response['trace'] = array_slice(
                array_map(fn($t) => [
                    'file' => $t['file'] ?? '',
                    'line' => $t['line'] ?? 0,
                    'function' => ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? ''),
                ], $e->getTrace()),
                0,
                15
            );
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ========================================================
    //  VALIDATION & AUTH ERROR RENDERING
    // ========================================================

    protected static function renderValidationError(ValidationException $e): void
    {
        // For non-AJAX: redirect back with errors in session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_errors'] = $e->errors();
            $_SESSION['_error_bag'] = $e->getErrorBag();

            // Flash old input (except dontFlash fields)
            $input = array_diff_key($_POST, array_flip(self::$dontFlash));
            $_SESSION['_old_input'] = $input;

            // Construct "Gagal Validasi" logic from old FormRequest
            $errors = $e->errors();
            $firstKey = array_key_first($errors);
            $firstMsg = is_array($errors[$firstKey]) ? $errors[$firstKey][0] : $errors[$firstKey];

            $redirectMsg = "Gagal Validasi: " . $firstMsg;
            if (count($errors) > 1) {
                $redirectMsg .= " (+" . (count($errors) - 1) . " error lainnya)";
            }

            $_SESSION['notification'] = [
                'status' => 'error',
                'message' => $redirectMsg
            ];
        }

        $redirectTo = $e->getRedirectTo() ?? ($_SERVER['HTTP_REFERER'] ?? '/');
        header("Location: {$redirectTo}", true, 302);
        exit;
    }

    protected static function renderAuthenticationError(AuthenticationException $e): void
    {
        $redirectTo = $e->redirectTo() ?? '/login';
        header("Location: {$redirectTo}", true, 302);
        exit;
    }

    // ========================================================
    //  CUSTOM RENDERERS & REPORTERS
    // ========================================================

    /**
     * Register a custom renderer for an exception type
     *
     * @example Handler::renderable(ModelNotFoundException::class, function($e) {
     *     return View::render('errors.model_not_found', ['model' => $e->getModel()]);
     * });
     */
    public static function renderable(string $exceptionClass, callable $callback): void
    {
        self::$renderers[$exceptionClass] = $callback;
    }

    /**
     * Register a custom reporter for an exception type
     *
     * @example Handler::reportable(QueryException::class, function($e, $context) {
     *     Sentry::captureException($e);
     * });
     */
    public static function reportable(string $exceptionClass, callable $callback): void
    {
        self::$reporters[$exceptionClass] = $callback;
    }

    /**
     * Register an external report hook (Sentry, Bugsnag, etc.)
     *
     * @example Handler::addReportHook(function($e, $context) {
     *     \Sentry\captureException($e);
     * });
     */
    public static function addReportHook(callable $callback): void
    {
        self::$reportHooks[] = $callback;
    }

    /**
     * Add exception class to dontReport list
     */
    public static function ignore(string ...$exceptions): void
    {
        self::$dontReport = array_merge(self::$dontReport, $exceptions);
    }

    /**
     * Try custom renderer
     */
    protected static function tryCustomRenderer(\Throwable $e): bool
    {
        foreach (self::$renderers as $type => $callback) {
            if ($e instanceof $type) {
                $callback($e);
                return true;
            }
        }
        return false;
    }

    // ========================================================
    //  ABORT HELPER (abort() function)
    // ========================================================

    /**
     * Abort with HTTP status code
     *
     * @example Handler::abort(404, 'Page not found');
     * @example Handler::abort(403);
     * @example Handler::abort(500, 'Something went wrong', ['X-Custom-Header' => 'value']);
     */
    public static function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new HttpException($code, $message, null, $headers);
    }

    /**
     * Abort if condition is true
     *
     * @example Handler::abortIf($user === null, 404, 'User not found');
     */
    public static function abortIf(bool $condition, int $code, string $message = '', array $headers = []): void
    {
        if ($condition) {
            self::abort($code, $message, $headers);
        }
    }

    /**
     * Abort unless condition is true
     *
     * @example Handler::abortUnless($user->isAdmin(), 403);
     */
    public static function abortUnless(bool $condition, int $code, string $message = '', array $headers = []): void
    {
        if (!$condition) {
            self::abort($code, $message, $headers);
        }
    }

    // ========================================================
    //  ERROR HANDLING (Warnings, Notices, etc.)
    // ========================================================

    private static function handleError($severity, $message, $file, $line, $env, $isFatal = false): void
    {
        $severityName = self::getSeverityName($severity);

        if (php_sapi_name() === 'cli') {
            echo "\n\033[1;41;97m {$severityName} \033[0m {$message}\n";
            echo "\033[38;5;244m at {$file}:{$line}\033[0m\n\n";
            return;
        }

        $originalFile = $file;
        $isBlade = false;

        // Blade Detection & Mapping (Cross-platform)
        [$file, $line, $isBlade] = self::detectBlade($file, $line, $message);

        $data = [
            'class' => $severityName,
            'error_code_text' => $isFatal ? 'Fatal Error' : 'System Warning',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'code_snippet' => self::getSnippet($file, $line),
            'trace_parsed' => [],
            'request_info' => self::getRequestInfo(true),
            'environment' => self::getEnvInfo($env),
            'error_code' => 500
        ];

        $view = $isBlade ? 'Internal::errors.viewfails' : ($isFatal ? 'Internal::errors.fatal' : 'Internal::errors.warning');

        try {
            View::render($view, $data);
        } catch (\Throwable $e) {
            self::renderCriticalFallback("Critical Handler Error", $message, $e->getMessage(), $file, $line);
        }
    }

    // ========================================================
    //  EXCEPTION HANDLING
    // ========================================================

    private static function handleException(\Throwable $e, string $env): void
    {
        if (php_sapi_name() === 'cli') {
            echo "\n\033[1;41;97m " . get_class($e) . " \033[0m " . $e->getMessage() . "\n";
            echo "\033[38;5;244m at " . $e->getFile() . ":" . $e->getLine() . "\033[0m\n\n";
            return;
        }

        $file = $e->getFile();
        $line = $e->getLine();

        // Blade Detection
        [$file, $line, $isBlade] = self::detectBlade($file, $line, $e->getMessage());

        $trace = self::parseTrace($e);

        // Previous exception chain
        $previousChain = [];
        $prev = $e->getPrevious();
        while ($prev) {
            $previousChain[] = [
                'class' => get_class($prev),
                'message' => $prev->getMessage(),
                'file' => $prev->getFile(),
                'line' => $prev->getLine(),
                'snippet' => self::getSnippet($prev->getFile(), $prev->getLine(), 3),
            ];
            $prev = $prev->getPrevious();
        }

        $data = [
            'class' => get_class($e),
            'error_code_text' => 'Exception',
            'message' => $e->getMessage(),
            'file' => $file,
            'line' => $line,
            'code_snippet' => self::getSnippet($file, $line),
            'trace_parsed' => $trace,
            'previous_chain' => $previousChain,
            'request_info' => self::getRequestInfo(true),
            'environment' => self::getEnvInfo($env),
            'error_code' => http_response_code(),
            'exception_context' => method_exists($e, 'context') ? call_user_func([$e, 'context']) : [],
        ];

        $view = $isBlade ? 'Internal::errors.viewfails' : 'Internal::errors.exception';

        try {
            if (ob_get_length())
                ob_clean();
            View::render($view, $data);
        } catch (\Throwable $th) {
            self::renderCriticalFallback("Exception Handler Failed", $e->getMessage(), $th->getMessage(), $file, $line);
        }
    }

    // ========================================================
    //  BLADE DETECTION (Extracted helper)
    // ========================================================

    private static function detectBlade(string $file, int $line, string $message = ''): array
    {
        $isBlade = false;
        $normalizedFile = str_replace('\\', '/', $file);

        if (str_contains($normalizedFile, 'storage/framework/views') || str_contains($normalizedFile, '.blade.php')) {
            $isBlade = true;
            if (str_contains($normalizedFile, 'storage/framework/views')) {
                $compiledContent = @file_get_contents($file);
                if ($compiledContent && preg_match('/\/\*\*PATH (.*?) ENDPATH\*\*\//', $compiledContent, $matches)) {
                    $originalBladeFile = $matches[1];

                    // Attempt to sync line number
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

        return [$file, $line, $isBlade];
    }

    // ========================================================
    //  TRACE PARSER (Extracted helper)
    // ========================================================

    private static function parseTrace(\Throwable $e): array
    {
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
                        return '"' . (strlen($a) > 50 ? substr($a, 0, 50) . '...' : $a) . '"';
                    if (is_null($a))
                        return 'null';
                    if (is_bool($a))
                        return $a ? 'true' : 'false';
                    return (string) $a;
                }, $t['args'] ?? [])
            ];
        }
        return $trace;
    }

    // ========================================================
    //  RENDER HELPERS
    // ========================================================

    private static function renderDatabaseError(\Throwable $e, string $env): void
    {
        if (php_sapi_name() === 'cli') {
            echo "\n\033[1;41;97m Database Exception \033[0m " . $e->getMessage() . "\n";
            echo "\033[38;5;244m at " . $e->getFile() . ":" . $e->getLine() . "\033[0m\n\n";
            return;
        }

        $data = [
            'message' => $e->getMessage(),
            'env_values' => $_ENV,
            'request_info' => self::getRequestInfo(),
            'environment' => ['php_version' => PHP_VERSION, 'app_env' => $env],
        ];

        // Include SQL info if available
        if ($e instanceof DatabaseException) {
            $data['sql'] = $e->getSql();
            $data['bindings'] = $e->getBindings();
            $data['error_type'] = match (true) {
                $e->isConnectionError() => 'Connection Error',
                $e->isDeadlock() => 'Deadlock Detected',
                $e->isUniqueConstraintError() => 'Duplicate Entry',
                $e->isSyntaxError() => 'SQL Syntax Error',
                default => 'Database Error',
            };
        }

        try {
            View::render('Internal::errors.database', $data);
        } catch (\Throwable $th) {
            self::renderCriticalFallback("Database Error", $e->getMessage(), $th->getMessage());
        }
    }

    private static function renderProductionError(int $status): void
    {
        $view = match ($status) {
            400 => 'Internal::errors.400',
            401 => 'Internal::errors.401',
            403 => 'Internal::errors.403',
            404 => 'Internal::errors.404',
            419 => 'Internal::errors.419',
            422 => 'Internal::errors.422',
            429 => 'Internal::errors.429',
            500 => 'Internal::errors.500',
            503 => 'Internal::errors.503',
            default => 'Internal::errors.500'
        };
        try {
            View::render($view);
        } catch (\Throwable $th) {
            // If specific view doesn't exist, try generic 500
            try {
                View::render('Internal::errors.500');
            } catch (\Throwable) {
                self::renderCriticalFallback("Error {$status}", HttpException::getDefaultMessage($status), $th->getMessage());
            }
        }
    }

    // ========================================================
    //  UTILITY METHODS
    // ========================================================

    private static function getEnvInfo(string $env): array
    {
        return [
            'php_version' => PHP_VERSION,
            'app_env' => $env,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'php_sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private static function renderCriticalFallback(string $title, string $message, string $renderError, ?string $file = null, ?int $line = null): void
    {
        $debug = Config::get('APP_DEBUG', 'false') === 'true';
        echo "<style>body{font-family:system-ui,-apple-system,sans-serif;background:#0f172a;color:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;box-sizing:border-box;}.card{background:#1e293b;padding:40px;border-radius:16px;max-width:600px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);border:1px solid #334155;}h1{font-size:24px;margin:0 0 12px 0;color:#f87171;}.debug-box{background:#020617;padding:16px;border-radius:8px;font-size:13px;font-family:'JetBrains Mono',monospace;color:#38bdf8;margin-top:12px;border:1px solid #1e3a5f;overflow-x:auto;word-break:break-all;}.status{color:#94a3b8;font-size:14px;}</style>";
        echo "<div class='card'><h1>" . htmlspecialchars($title) . "</h1><p>" . htmlspecialchars($message) . "</p>";
        if ($debug) {
            if ($file)
                echo "<div class='debug-box'>📄 Location: " . htmlspecialchars($file) . ":" . $line . "</div>";
            echo "<div class='debug-box'>⚠️ Render Error: " . htmlspecialchars($renderError) . "</div>";
        }
        echo "<p class='status'>HTTP " . http_response_code() . "</p></div>";
    }

    private static function getSnippet(string $file, int $line, int $linesAround = 10): array
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

    private static function getSeverityName(int $severity): string
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
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            default => 'FATAL_ERROR'
        };
    }

    private static function getRequestInfo(bool $includeQuery = false): array
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['HTTP_X_REAL_IP']
                ?? $_SERVER['REMOTE_ADDR']
                ?? '127.0.0.1',
            'query' => $includeQuery ? $_GET : [],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
        ];
    }

    // ========================================================
    //  CONFIGURATION METHODS
    // ========================================================

    /**
     * Set max reports per fingerprint for rate limiting
     */
    public static function throttleReports(int $max): void
    {
        self::$maxReportsPerFingerprint = $max;
    }

    /**
     * Set custom log file path
     */
    public static function setLogPath(string $path): void
    {
        self::$logFile = $path;
    }

    /**
     * Get dont flash fields
     */
    public static function getDontFlash(): array
    {
        return self::$dontFlash;
    }

    /**
     * Add fields to dontFlash list
     */
    public static function dontFlash(string ...$fields): void
    {
        self::$dontFlash = array_merge(self::$dontFlash, $fields);
    }

    /**
     * Reset all configuration (useful for testing)
     */
    public static function flush(): void
    {
        self::$dontReport = [];
        self::$renderers = [];
        self::$reporters = [];
        self::$reportHooks = [];
        self::$reportedFingerprints = [];
    }
}

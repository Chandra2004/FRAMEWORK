<?php

namespace TheFramework\App\Http;

/**
 * Response — HTTP Response Abstraction (Enterprise-Grade)
 * 
 * Menyediakan object-oriented response yang testable.
 * Mendukung: JSON, HTML, Redirect, Download, Stream, CORS, Macros.
 * 
 * Terinspirasi oleh Symfony\Component\HttpFoundation\Response
 * dan Illuminate\Http\Response.
 * 
 * @package TheFramework\App\Http
 * @version 5.1.0
 */
class Response
{
    /**
     * Response content
     */
    protected string $content = '';

    /**
     * HTTP status code
     */
    protected int $statusCode = 200;

    /**
     * HTTP headers
     */
    protected array $headers = [];

    /**
     * Cookies to set
     */
    protected array $cookies = [];

    /**
     * Whether the response has been sent
     */
    protected bool $sent = false;

    /**
     * Registered macros [name => callable]
     */
    protected static array $macros = [];

    /**
     * Standard HTTP status texts
     */
    protected static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        410 => 'Gone',
        413 => 'Payload Too Large',
        415 => 'Unsupported Media Type',
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    // ========================================================
    //  CONSTRUCTORS & FACTORIES
    // ========================================================

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    /**
     * Factory: buat response baru
     */
    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    /**
     * Factory: JSON response
     */
    public static function json($data = [], int $status = 200, array $headers = [], int $options = 0): static
    {
        $response = new static('', $status, $headers);
        $response->header('Content-Type', 'application/json; charset=utf-8');

        $json = json_encode($data, $options | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }

        $response->setContent($json);
        return $response;
    }

    /**
     * Factory: View/HTML response
     */
    public static function view(string $view, array $data = [], int $status = 200, array $headers = []): static
    {
        ob_start();
        View::render($view, $data);
        $content = ob_get_clean();

        $response = new static($content, $status, $headers);
        $response->header('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Factory: Redirect response
     */
    public static function redirect(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status, $headers);
    }

    /**
     * Factory: No Content response (204)
     */
    public static function noContent(int $status = 204, array $headers = []): static
    {
        return new static('', $status, $headers);
    }

    /**
     * Factory: Download response (Memory-efficient streaming)
     */
    public static function download(string $filePath, ?string $name = null, array $headers = []): static
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $name = $name ?? basename($filePath);
        $size = filesize($filePath);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        $response = static::stream(function () use ($filePath) {
            $stream = fopen($filePath, 'rb');
            while (!feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            fclose($stream);
        }, 200, $headers);

        $response->header('Content-Type', $mime);
        $response->header('Content-Disposition', "attachment; filename=\"{$name}\"");
        $response->header('Content-Length', (string) $size);
        $response->header('Cache-Control', 'no-cache, must-revalidate');

        return $response;
    }

    /**
     * Factory: Stream a file with progress callback support
     */
    public static function streamFile(string $filePath, ?callable $progress = null, array $headers = []): static
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $size = filesize($filePath);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        $response = static::stream(function () use ($filePath, $size, $progress) {
            $stream = fopen($filePath, 'rb');
            $sent = 0;
            while (!feof($stream)) {
                $chunkSize = 8192;
                $buffer = fread($stream, $chunkSize);
                $sent += strlen($buffer);
                
                echo $buffer;
                flush();

                if ($progress) {
                    $progress($sent, $size);
                }
            }
            fclose($stream);
        }, 200, $headers);

        $response->header('Content-Type', $mime);
        $response->header('Content-Length', (string) $size);

        return $response;
    }

    /**
     * Factory: Stream response (for large files)
     */
    public static function stream(callable $callback, int $status = 200, array $headers = []): static
    {
        $response = new static('', $status, $headers);
        $response->streamCallback = $callback;
        return $response;
    }

    /**
     * Stream callback (if streamed response)
     */
    protected $streamCallback = null;

    // ========================================================
    //  CONTENT
    // ========================================================

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    // ========================================================
    //  STATUS CODE
    // ========================================================

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function status(int $code): static
    {
        return $this->setStatusCode($code);
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    // ========================================================
    //  HEADERS
    // ========================================================

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withHeaders(array $headers): static
    {
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    public function hasHeader(string $key): bool
    {
        return isset($this->headers[$key]);
    }

    public function removeHeader(string $key): static
    {
        unset($this->headers[$key]);
        return $this;
    }

    // ========================================================
    //  COOKIES
    // ========================================================

    public function cookie(
        string $name,
        string $value = '',
        int $minutes = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): static {
        $this->cookies[] = [
            'name'     => $name,
            'value'    => $value,
            'expire'   => $minutes > 0 ? time() + ($minutes * 60) : 0,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ];
        return $this;
    }

    public function withoutCookie(string $name, string $path = '/', string $domain = ''): static
    {
        return $this->cookie($name, '', -2628000, $path, $domain);
    }

    // ========================================================
    //  CACHE CONTROL
    // ========================================================

    public function withCache(int $seconds, bool $public = true): static
    {
        return $this->setTtl($seconds, $public);
    }

    /**
     * Set Cache-Control TTL
     */
    public function setTtl(int $seconds, bool $public = true): static
    {
        $visibility = $public ? 'public' : 'private';
        $this->header('Cache-Control', "{$visibility}, max-age={$seconds}");
        $this->header('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
        return $this;
    }

    public function public(): static
    {
        $current = $this->getHeader('Cache-Control') ?? '';
        $this->header('Cache-Control', str_replace('private', 'public', $current));
        return $this;
    }

    public function private(): static
    {
        $current = $this->getHeader('Cache-Control') ?? '';
        $this->header('Cache-Control', str_replace('public', 'private', $current));
        return $this;
    }

    public function withoutCache(): static
    {
        $this->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->header('Pragma', 'no-cache');
        $this->header('Expires', '0');
        return $this;
    }

    // ========================================================
    //  CORS
    // ========================================================

    public function withCors(
        string $origin = '*',
        array $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        bool $credentials = false,
        int $maxAge = 86400
    ): static {
        $this->header('Access-Control-Allow-Origin', $origin);
        $this->header('Access-Control-Allow-Methods', implode(', ', $methods));
        $this->header('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
        $this->header('Access-Control-Max-Age', (string) $maxAge);

        if ($credentials) {
            $this->header('Access-Control-Allow-Credentials', 'true');
        }

        return $this;
    }

    // ========================================================
    //  SEND
    // ========================================================

    /**
     * Send the HTTP response
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        // Clear any existing output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // Send cookies
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                [
                    'expires'  => $cookie['expire'],
                    'path'     => $cookie['path'],
                    'domain'   => $cookie['domain'],
                    'secure'   => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite'],
                ]
            );
        }

        // Send content or stream
        if ($this->streamCallback) {
            ($this->streamCallback)();
        } else {
            echo $this->content;
        }

        $this->sent = true;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    // ========================================================
    //  MACROS (Extensibility)
    // ========================================================

    /**
     * Register a custom macro
     * 
     * @example Response::macro('success', function($data) {
     *     return Response::json(['success' => true, 'data' => $data]);
     * });
     * 
     * @example Response::success($userData)
     */
    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public static function __callStatic(string $method, array $parameters)
    {
        if (isset(static::$macros[$method])) {
            return call_user_func_array(static::$macros[$method], $parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on Response.");
    }

    public function __call(string $method, array $parameters)
    {
        if (isset(static::$macros[$method])) {
            return call_user_func_array(static::$macros[$method], array_merge([$this], $parameters));
        }

        throw new \BadMethodCallException("Method {$method} does not exist on Response.");
    }

    // ========================================================
    //  CONVERSION & UTILITIES
    // ========================================================

    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * Get status text for a given code
     */
    public static function statusText(int $code): string
    {
        return static::$statusTexts[$code] ?? 'Unknown';
    }
}


/**
 * RedirectResponse — Fluent Redirect Builder
 */
class RedirectResponse extends Response
{
    protected string $targetUrl;
    protected array $flashData = [];

    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        parent::__construct('', $status, $headers);
        $this->targetUrl = $url;
        $this->header('Location', $url);
    }

    /**
     * Redirect target URL
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * Flash data ke session
     * 
     * @example redirect('/home')->with('status', 'Profile updated!')
     */
    public function with(string|array $key, $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->flashData[$k] = $v;
            }
        } else {
            $this->flashData[$key] = $value;
        }
        return $this;
    }

    /**
     * Flash error messages
     */
    public function withErrors(array $errors, string $bag = 'default'): static
    {
        $this->flashData['_errors'] = $errors;
        $this->flashData['_error_bag'] = $bag;
        return $this;
    }

    /**
     * Flash old input data (with sensitive fields stripped)
     */
    public function withInput(?array $input = null): static
    {
        $dontFlash = ['password', 'password_confirmation', 'current_password', 'token', 'secret', 'credit_card', 'cvv'];
        $data = $input ?? $_POST;
        $data = array_diff_key($data, array_flip($dontFlash));
        $this->flashData['_old_input'] = $data;
        return $this;
    }

    /**
     * Flash notification message (compatible with existing flash system)
     */
    public function withNotification(string $status, string $message): static
    {
        $this->flashData['notification'] = [
            'status'  => $status,
            'message' => $message,
        ];
        return $this;
    }

    /**
     * Override send to flash data before redirecting
     */
    public function send(): void
    {
        // Flash all data to session
        if (!empty($this->flashData) && session_status() === PHP_SESSION_ACTIVE) {
            foreach ($this->flashData as $key => $value) {
                $_SESSION[$key] = $value;
            }
        }

        parent::send();
    }

    /**
     * Static helper: redirect back
     */
    public static function back(int $status = 302): static
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        return new static($url, $status);
    }

    /**
     * Static helper: redirect to named route
     */
    public static function route(string $name, array $params = [], int $status = 302): static
    {
        $url = Router::url($name, $params);
        return new static($url, $status);
    }
}

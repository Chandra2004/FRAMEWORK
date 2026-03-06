<?php

namespace TheFramework\App\Http;

use TheFramework\App\Exceptions\ValidationException;

class Request
{
    protected array $input = [];
    protected array $files = [];
    protected static array $routeParams = [];
    protected bool $forceJson = false;
    protected ?string $content = null;

    public function __construct()
    {
        // 1. Merge GET & POST
        $this->input = array_merge($_GET ?? [], $_POST ?? []);

        // 2. Parse JSON Input
        if ($this->isJson()) {
            $json = json_decode($this->getContent(), true);
            if (is_array($json)) {
                $this->input = array_merge($this->input, $json);
            }
        }

        // 3. Normalize Files Array
        foreach ($_FILES ?? [] as $key => $file) {
            if (is_array($file['name'])) {
                $files = [];
                foreach ($file['name'] as $i => $name) {
                    $files[] = [
                        'name' => $name,
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i],
                    ];
                }
                $this->files[$key] = $files;
            } else {
                $this->files[$key] = $file;
            }
            // Put files into input array for unified access
            $this->input[$key] = $this->files[$key];
        }
    }

    // ========================================================
    //  INPUT RETRIEVAL
    // ========================================================

    public function all(): array
    {
        return array_merge($this->input, $this->files);
    }

    public function input(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->input;
        }
        return $this->input[$key] ?? $default;
    }

    public function query(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public function post(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get route parameter
     */
    public function route(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return self::$routeParams;
        }
        return self::$routeParams[$key] ?? $default;
    }

    /**
     * Internal: Set route parameters from Router
     */
    public static function setRouteParams(array $params): void
    {
        self::$routeParams = $params;
    }

    public function only($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->input)) {
                $results[$key] = $this->input[$key];
            }
        }
        return $results;
    }

    public function except($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = $this->input;
        foreach ($keys as $key) {
            unset($results[$key]);
        }
        return $results;
    }

    // ========================================================
    //  INPUT PRESENCE
    // ========================================================

    public function has($keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->input)) {
                return false;
            }
        }
        return true;
    }

    public function hasAny($keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->input)) {
                return true;
            }
        }
        return false;
    }

    public function filled($keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if (empty($this->input[$key])) {
                return false;
            }
        }
        return true;
    }

    public function anyFilled($keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if (!empty($this->input[$key])) {
                return true;
            }
        }
        return false;
    }

    // ========================================================
    //  INPUT CASTING
    // ========================================================

    public function boolean(string $key): bool
    {
        return filter_var($this->input($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function date(string $key, string $format = null, string $tz = null): ?\DateTimeInterface
    {
        if ($this->isMissing($key)) {
            return null;
        }

        $value = $this->input($key);
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        try {
            $date = is_null($format)
                ? new \DateTime($value)
                : \DateTime::createFromFormat($format, $value);
            
            if ($tz) {
                $date->setTimezone(new \DateTimeZone($tz));
            }
            return $date ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function integer(string $key, int $default = 0): int
    {
        return (int) $this->input($key, $default);
    }

    public function float(string $key, float $default = 0.0): float
    {
        return (float) $this->input($key, $default);
    }

    public function string(string $key, string $default = ''): string
    {
        return (string) $this->input($key, $default);
    }

    public function isMissing(string $key): bool
    {
        return !array_key_exists($key, $this->input);
    }

    // ========================================================
    //  FILES
    // ========================================================

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) &&
            is_array($this->files[$key]) &&
            isset($this->files[$key]['error']) &&
            $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function file(string $key): ?array
    {
        return $this->hasFile($key) ? $this->files[$key] : null;
    }

    // ========================================================
    //  URL & METHOD INFO
    // ========================================================

    public function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }
        return $method;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return trim(parse_url($uri, PHP_URL_PATH), '/');
    }

    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', $this->fullUrl()), '/');
    }

    public function fullUrl(): string
    {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $protocol = $isSecure ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$protocol}://{$host}{$uri}";
    }

    public function ip(): string
    {
        $trustedProxy = \TheFramework\App\Core\Config::get('TRUSTED_PROXY', false);
        if ($trustedProxy) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($ips[0]);
            }
            return $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    // ========================================================
    //  HEADERS & TOKENS
    // ========================================================

    public function header(string $key = null, $default = null)
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (!$headers) {
            foreach ($_SERVER as $name => $value) {
                if (str_starts_with($name, 'HTTP_')) {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$headerName] = $value;
                }
            }
        }
        
        // Normalize keys to lowercase for case-insensitive lookup
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);

        if (is_null($key)) {
            return $normalizedHeaders;
        }

        return $normalizedHeaders[strtolower($key)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    public function server(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $_SERVER;
        }
        return $_SERVER[$key] ?? $default;
    }

    // ========================================================
    //  CONTENT NEGOTIATION
    // ========================================================

    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        return str_contains(strtolower($contentType), 'application/json');
    }

    public function expectsJson(): bool
    {
        return ($this->ajax() && !$this->pjax()) || $this->wantsJson();
    }

    public function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function ajax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public function pjax(): bool
    {
        return (bool) ($this->header('X-PJAX') ?? false);
    }

    // ========================================================
    //  VALIDATION OVERRIDE
    // ========================================================

    public function validate(array $rules, array $labels = []): array
    {
        $validator = new Validator();
        $isValid = $validator->validate($this->input, $rules, $labels);

        if (!$isValid) {
            throw ValidationException::withMessages($validator->errors());
        }

        return array_intersect_key($this->input, $rules);
    }
}

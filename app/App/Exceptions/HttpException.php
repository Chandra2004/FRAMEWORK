<?php

namespace TheFramework\App\Exceptions;

/**
 * HttpException — Base exception for HTTP errors
 * Carries status code, headers, and error context.
 */
class HttpException extends \RuntimeException
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(
        int $statusCode = 500,
        string $message = '',
        ?\Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        if (empty($message)) {
            $message = self::getDefaultMessage($statusCode);
        }

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    public static function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
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
            default => 'HTTP Error ' . $statusCode,
        };
    }
}

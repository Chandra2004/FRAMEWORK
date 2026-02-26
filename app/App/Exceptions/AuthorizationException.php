<?php

namespace TheFramework\App\Exceptions;

/**
 * AuthorizationException — thrown when user is not authorized
 * Auto-resolves ke HTTP 403
 */
class AuthorizationException extends \RuntimeException
{
    protected $response = null;
    protected int $statusCode = 403;

    public function __construct(string $message = 'This action is unauthorized.', $response = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->response = $response;
    }

    public function response()
    {
        return $this->response;
    }

    public function withStatus(int $status): static
    {
        $this->statusCode = $status;
        return $this;
    }

    public function asNotFound(): static
    {
        $this->statusCode = 404;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toResponse(): array
    {
        return [
            'message' => $this->getMessage(),
            'status' => $this->statusCode,
        ];
    }
}

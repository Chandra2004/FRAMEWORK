<?php

namespace TheFramework\App\Exceptions;

/**
 * AuthenticationException — thrown when user is not authenticated
 * Auto-resolves ke HTTP 401
 */
class AuthenticationException extends \RuntimeException
{
    protected array $guards;
    protected ?string $redirectTo;

    public function __construct(string $message = 'Unauthenticated.', array $guards = [], ?string $redirectTo = null)
    {
        parent::__construct($message, 401);
        $this->guards = $guards;
        $this->redirectTo = $redirectTo;
    }

    public function guards(): array
    {
        return $this->guards;
    }

    public function redirectTo(): ?string
    {
        return $this->redirectTo;
    }

    public function getStatusCode(): int
    {
        return 401;
    }
}

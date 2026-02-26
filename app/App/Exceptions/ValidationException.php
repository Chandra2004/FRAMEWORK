<?php

namespace TheFramework\App\Exceptions;

/**
 * ValidationException — thrown when input validation fails
 * Auto-resolves ke HTTP 422
 */
class ValidationException extends \RuntimeException
{
    protected array $errors = [];
    protected string $errorBag = 'default';
    protected ?string $redirectTo = null;
    protected $response = null;
    protected $validator = null;

    public function __construct($validator = null, ?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        if (is_array($validator)) {
            $this->errors = $validator;
            $message = $message ?? 'The given data was invalid.';
        } elseif (is_object($validator)) {
            $this->validator = $validator;
            if (method_exists($validator, 'errors')) {
                $this->errors = $validator->errors();
            }
            $message = $message ?? 'The given data was invalid.';
        }

        parent::__construct($message ?? 'The given data was invalid.', $code ?: 422, $previous);
    }

    /**
     * Create from array of error messages
     */
    public static function withMessages(array $messages): static
    {
        return new static($messages);
    }

    /**
     * Get all validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return 422;
    }

    /**
     * Set the error bag name
     */
    public function errorBag(string $bag): static
    {
        $this->errorBag = $bag;
        return $this;
    }

    /**
     * Get the error bag name
     */
    public function getErrorBag(): string
    {
        return $this->errorBag;
    }

    /**
     * Set redirect URL after failed validation
     */
    public function redirectTo(string $url): static
    {
        $this->redirectTo = $url;
        return $this;
    }

    /**
     * Get redirect URL
     */
    public function getRedirectTo(): ?string
    {
        return $this->redirectTo;
    }

    /**
     * Set custom response
     */
    public function response($response): static
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get custom response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Convert to JSON-friendly format
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }
}

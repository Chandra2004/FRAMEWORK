<?php

namespace TheFramework\App\TFWire\Security;

/**
 * Security Exception for TFWire state tampering or invalid requests
 */
class SecurityException extends \RuntimeException
{
    public function __construct(string $message = 'Security violation detected', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct("[TFWire Security] {$message}", $code, $previous);
    }
}

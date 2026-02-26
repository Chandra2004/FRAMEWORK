<?php

namespace TheFramework\App\Exceptions;

/**
 * AccessDeniedHttpException — 403
 */
class AccessDeniedHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(403, $message, $previous, $headers);
    }
}

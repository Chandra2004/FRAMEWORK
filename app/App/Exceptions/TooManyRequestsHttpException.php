<?php

namespace TheFramework\App\Exceptions;

/**
 * TooManyRequestsHttpException — 429
 */
class TooManyRequestsHttpException extends HttpException
{
    public function __construct(?int $retryAfter = null, string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }
        parent::__construct(429, $message, $previous, $headers);
    }
}

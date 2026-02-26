<?php

namespace TheFramework\App\Exceptions;

/**
 * ServiceUnavailableHttpException — 503
 */
class ServiceUnavailableHttpException extends HttpException
{
    public function __construct(?int $retryAfter = null, string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = $retryAfter;
        }
        parent::__construct(503, $message, $previous, $headers);
    }
}

<?php

namespace TheFramework\App\Exceptions;

/**
 * GoneHttpException — 410
 */
class GoneHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(410, $message, $previous, $headers);
    }
}

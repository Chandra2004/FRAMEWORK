<?php

namespace TheFramework\App\Exceptions;

/**
 * ConflictHttpException — 409
 */
class ConflictHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(409, $message, $previous, $headers);
    }
}

<?php

namespace TheFramework\App\Exceptions;

/**
 * NotFoundHttpException — 404
 */
class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(404, $message, $previous, $headers);
    }
}

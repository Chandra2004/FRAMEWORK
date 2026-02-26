<?php

namespace TheFramework\App\Exceptions;

/**
 * MethodNotAllowedHttpException — 405
 */
class MethodNotAllowedHttpException extends HttpException
{
    public function __construct(array $allow = [], string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        if (!empty($allow)) {
            $headers['Allow'] = implode(', ', $allow);
        }
        parent::__construct(405, $message, $previous, $headers);
    }
}

<?php

namespace TheFramework\App\Exceptions;

/**
 * UnauthorizedHttpException — 401
 */
class UnauthorizedHttpException extends HttpException
{
    public function __construct(string $challenge = '', string $message = '', ?\Throwable $previous = null, array $headers = [])
    {
        if ($challenge) {
            $headers['WWW-Authenticate'] = $challenge;
        }
        parent::__construct(401, $message, $previous, $headers);
    }
}

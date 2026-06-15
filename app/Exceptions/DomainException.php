<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class DomainException extends Exception
{
    public function __construct(
        string $message = '',
        public readonly int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}

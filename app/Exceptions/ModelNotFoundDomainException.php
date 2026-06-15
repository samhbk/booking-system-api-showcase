<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ModelNotFoundDomainException extends DomainException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}

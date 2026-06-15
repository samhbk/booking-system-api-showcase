<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidBookingWindowException extends DomainException
{
    public function __construct(string $message = 'Invalid booking window.')
    {
        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}

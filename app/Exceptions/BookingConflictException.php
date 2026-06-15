<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BookingConflictException extends DomainException
{
    public function __construct(string $message = 'This time slot is already booked.')
    {
        parent::__construct($message, Response::HTTP_CONFLICT);
    }
}

<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BookingNotFoundException extends DomainException
{
    public function __construct(string $message = 'Booking not found.')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}

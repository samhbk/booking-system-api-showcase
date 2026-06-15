<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return $this === self::Pending || $this === self::Confirmed;
    }
}

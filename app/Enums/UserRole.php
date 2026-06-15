<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}

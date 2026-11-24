<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Sales = 'sales';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Sales => 'Sales',
        };
    }
}

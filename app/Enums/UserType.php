<?php

namespace App\Enums;

enum UserType: string
{
    case Student = 'student';
    case Faculty = 'faculty';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Faculty => 'Faculty',
            self::Admin => 'Admin',
        };
    }
}

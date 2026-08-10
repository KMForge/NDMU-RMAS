<?php

namespace App\Enums;

enum ConsultationMode: string
{
    case InPerson = 'in_person';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::InPerson => 'In Person',
            self::Online => 'Online',
        };
    }
}

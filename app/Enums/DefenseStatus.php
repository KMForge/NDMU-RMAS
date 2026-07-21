<?php

namespace App\Enums;

enum DefenseStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}

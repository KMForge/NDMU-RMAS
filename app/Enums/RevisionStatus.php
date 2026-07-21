<?php

namespace App\Enums;

enum RevisionStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
}

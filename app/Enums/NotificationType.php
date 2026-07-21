<?php

namespace App\Enums;

enum NotificationType: string
{
    case System = 'system';
    case Research = 'research';
    case Document = 'document';
    case Defense = 'defense';
    case Evaluation = 'evaluation';
}

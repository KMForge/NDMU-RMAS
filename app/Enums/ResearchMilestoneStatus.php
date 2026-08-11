<?php

namespace App\Enums;

enum ResearchMilestoneStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::NotApplicable => 'Not Applicable',
        };
    }
}

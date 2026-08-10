<?php

namespace App\Enums;

enum ConsultationStatus: string
{
    case Pending = 'pending';
    case RescheduleProposed = 'reschedule_proposed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::RescheduleProposed => 'Reschedule Proposed',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}

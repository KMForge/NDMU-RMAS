<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}

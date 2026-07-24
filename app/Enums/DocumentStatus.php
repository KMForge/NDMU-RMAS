<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequested = 'revision_requested';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}

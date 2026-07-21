<?php

namespace App\Enums;

enum ResearchStage: string
{
    case Proposal = 'proposal';
    case InProgress = 'in_progress';
    case Defense = 'defense';
    case Completed = 'completed';
    case Archived = 'archived';
}

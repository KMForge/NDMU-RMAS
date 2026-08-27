<?php

namespace App\Enums;

enum DefenseType: string
{
    case TitlePresentation = 'title_presentation';
    case ProposalDefense = 'proposal_defense';
    case PreFinalDefense = 'pre_final_defense';
    case FinalDefense = 'final_defense';
}

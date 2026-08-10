<?php

namespace App\Enums;

enum DocumentStage: string
{
    case TitleProposal = 'title_proposal';
    case ProposalDefense = 'proposal_defense';
    case PreFinalDefense = 'pre_final_defense';
    case FinalDefense = 'final_defense';
    case FinalManuscript = 'final_manuscript';

    public function label(): string
    {
        return match ($this) {
            self::TitleProposal => 'Title Proposal',
            self::ProposalDefense => 'Proposal Defense',
            self::PreFinalDefense => 'Pre-Final Defense',
            self::FinalDefense => 'Final Defense',
            self::FinalManuscript => 'Final Manuscript',
        };
    }
}

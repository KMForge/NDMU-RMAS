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

    public function defenseType(): ?string
    {
        return match ($this) {
            self::TitleProposal => 'title_presentation',
            self::ProposalDefense => 'proposal_defense',
            self::PreFinalDefense => 'pre_final_defense',
            self::FinalDefense => 'final_defense',
            self::FinalManuscript => null,
        };
    }
}

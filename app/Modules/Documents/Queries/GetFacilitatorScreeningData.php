<?php

namespace App\Modules\Documents\Queries;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetFacilitatorScreeningData
{
    /** @return array<string, mixed> */
    public function for(User $facilitator): array
    {
        $ownedDocuments = Document::query()
            ->whereHas('researchClassGroup.researchClass', fn (Builder $query) => $query
                ->where('facilitator_id', $facilitator->getKey()));

        $titleProposalScreeningQueue = (clone $ownedDocuments)
            ->with(['user:id,name,email', 'researchClassGroup:id,research_class_id,name'])
            ->where('document_stage', DocumentStage::TitleProposal->value)
            ->where('status', DocumentStatus::Submitted->value)
            ->where('is_current', true)
            ->latest('submitted_at')
            ->get();

        $adviserApprovedDefenseDocuments = (clone $ownedDocuments)
            ->with([
                'user:id,name,email',
                'researchClassGroup:id,research_class_id,name,adviser_id',
                'researchClassGroup.adviser:id,name',
            ])
            ->whereIn('document_stage', $this->defenseStages())
            ->where('status', DocumentStatus::Accepted->value)
            ->where('is_current', true)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('defenses')
                    ->whereColumn('defenses.research_class_group_id', 'documents.research_class_group_id')
                    ->whereColumn('defenses.defense_type', 'documents.document_stage')
                    ->whereNotIn('defenses.status', ['cancelled']);
            })
            ->latest('submitted_at')
            ->get();

        // This is an immutable workflow history, not a second pending queue. It
        // deliberately includes superseded file versions so approved/revised
        // submissions never disappear after a decision or a later upload.
        $history = (clone $ownedDocuments)
            ->with([
                'user:id,name,email',
                'researchClassGroup:id,research_class_id,name,adviser_id',
                'researchClassGroup.adviser:id,name',
                'reviews' => fn ($query) => $query
                    ->where('is_superseded', false)
                    ->with('reviewer:id,name')
                    ->latest('reviewed_at'),
            ])
            ->where(function (Builder $query): void {
                $query->where(function (Builder $title): void {
                    $title->where('document_stage', DocumentStage::TitleProposal->value)
                        ->whereIn('status', [
                            DocumentStatus::Submitted->value,
                            DocumentStatus::RevisionRequested->value,
                            DocumentStatus::ApprovedForPresentation->value,
                        ]);
                })->orWhere(function (Builder $defense): void {
                    $defense->whereIn('document_stage', $this->defenseStages())
                        ->whereIn('status', [
                            DocumentStatus::Accepted->value,
                            DocumentStatus::RevisionRequested->value,
                            DocumentStatus::Rejected->value,
                        ]);
                });
            })
            ->latest('submitted_at')
            ->limit(50)
            ->get();

        return [
            'titleProposalScreeningQueue' => $titleProposalScreeningQueue,
            'adviserApprovedDefenseDocuments' => $adviserApprovedDefenseDocuments,
            'facilitatorScreeningHistory' => $history,
            'facilitatorScreeningStats' => [
                'approved' => $history->whereIn('status', [
                    DocumentStatus::ApprovedForPresentation,
                    DocumentStatus::Accepted,
                ])->count(),
                'pending' => $history->where('status', DocumentStatus::Submitted)->count(),
                'revisions' => $history->where('status', DocumentStatus::RevisionRequested)->count(),
                'total' => $history->count(),
            ],
        ];
    }

    /** @return list<string> */
    private function defenseStages(): array
    {
        return [
            DocumentStage::ProposalDefense->value,
            DocumentStage::PreFinalDefense->value,
            DocumentStage::FinalDefense->value,
        ];
    }
}

<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\TitlePresentation;
use App\Models\User;

class ReconcileWorkflowMilestones
{
    public function __construct(private readonly SynchronizeWorkflowMilestone $synchronizeMilestone) {}

    /** @return array{started: int, completed: int} */
    public function execute(): array
    {
        $started = 0;
        $completed = 0;

        Document::query()
            ->where('document_stage', DocumentStage::TitleProposal->value)
            ->where('is_current', true)
            ->whereIn('status', [
                DocumentStatus::Submitted->value,
                DocumentStatus::UnderReview->value,
                DocumentStatus::RevisionRequested->value,
                DocumentStatus::ApprovedForPresentation->value,
                DocumentStatus::Accepted->value,
                DocumentStatus::Rejected->value,
            ])
            ->with(['researchClassGroup', 'user'])
            ->each(function (Document $document) use (&$started): void {
                if ($document->researchClassGroup === null || $document->user === null) {
                    return;
                }

                $this->synchronizeMilestone->start(
                    $document->researchClassGroup,
                    'research-title-presentation',
                    $document->user,
                    'document',
                    $document->getKey(),
                    "Title Proposal v{$document->version_number} entered the formal screening workflow.",
                );
                $started++;
            });

        TitlePresentation::query()
            ->where('status', 'finalized')
            ->whereNotNull('finalized_by')
            ->with('defense.group')
            ->each(function (TitlePresentation $presentation) use (&$completed): void {
                $group = $presentation->defense?->group;
                $actor = User::query()->find($presentation->finalized_by);
                if ($group === null || $actor === null) {
                    return;
                }

                $this->synchronizeMilestone->complete(
                    $group,
                    'research-title-presentation',
                    $actor,
                    'official_form',
                    $presentation->official_form_instance_id,
                    'Finalized RES-026 Research Title Approval with all required digital signatures.',
                );
                $completed++;
            });

        return compact('started', 'completed');
    }
}

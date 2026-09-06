<?php

namespace App\Modules\ResearchProgress\Actions;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Models\DefenseEvaluationRound;
use App\Models\Document;
use App\Models\OfficialFormInstance;
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

        // Stage 1: Title Proposal screening
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

        // Stage 1: Title Presentation finalized
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

        // Stage 2: Proposal Document approved
        Document::query()
            ->whereIn('document_stage', [
                DocumentStage::ProposalDefense->value,
                'proposal',
                'proposal_manuscript',
            ])
            ->where('is_current', true)
            ->whereIn('status', [
                DocumentStatus::ApprovedForPresentation->value,
                DocumentStatus::Accepted->value,
            ])
            ->with(['researchClassGroup', 'user'])
            ->each(function (Document $document) use (&$completed): void {
                if ($document->researchClassGroup === null) {
                    return;
                }

                $actor = $document->user ?? User::query()->first();
                if ($actor === null) {
                    return;
                }

                $this->synchronizeMilestone->complete(
                    $document->researchClassGroup,
                    'formulation-research-proposal',
                    $actor,
                    'document',
                    $document->getKey(),
                    'Research Proposal document approved for Proposal Defense.',
                );
                $completed++;
            });

        // Stage 3: Proposal Defense finalized / RES-037 signed
        DefenseEvaluationRound::query()
            ->whereIn('status', ['finalized', 'released', 'complete'])
            ->with(['defense.group', 'summarySigner'])
            ->each(function (DefenseEvaluationRound $round) use (&$completed): void {
                $group = $round->defense?->group;
                $defenseType = $round->defense?->defense_type ?? '';
                if ($group === null) {
                    return;
                }

                $milestoneCode = in_array($defenseType, ['final_defense', 'final_oral_defense'], true)
                    ? 'research-final-oral-defense'
                    : 'research-proposal-defense';

                $actor = $round->summarySigner ?? User::query()->find($round->opened_by) ?? User::query()->first();
                if ($actor === null) {
                    return;
                }

                $this->synchronizeMilestone->complete(
                    $group,
                    $milestoneCode,
                    $actor,
                    'evaluation_round',
                    $round->id,
                    "Finalized defense evaluation summary ({$milestoneCode}).",
                );
                $completed++;
            });

        // Stage 4: RES-041 approved
        OfficialFormInstance::query()
            ->whereHas('definition', fn ($q) => $q->where('code', 'RES-041'))
            ->whereIn('status', ['approved', 'completed'])
            ->with(['researchClass.groups', 'group'])
            ->each(function (OfficialFormInstance $instance) use (&$completed): void {
                $groups = $instance->researchClass?->groups ?? ($instance->group ? collect([$instance->group]) : collect());
                $actor = User::query()->find($instance->initiated_by) ?? User::query()->first();
                if ($actor === null) {
                    return;
                }

                foreach ($groups as $group) {
                    $this->synchronizeMilestone->complete(
                        $group,
                        'revision-research-proposal',
                        $actor,
                        'official_form',
                        $instance->id,
                        'Endorsement to Program Coordinator (RES-041) completed.',
                    );
                    $completed++;
                }
            });

        // Stage 5: RES-043B completed
        OfficialFormInstance::query()
            ->whereHas('definition', fn ($q) => $q->where('code', 'RES-043B'))
            ->whereIn('status', ['completed', 'validated', 'approved', 'signed'])
            ->with('group')
            ->each(function (OfficialFormInstance $instance) use (&$completed): void {
                if ($instance->group === null) {
                    return;
                }

                $actor = User::query()->find($instance->initiated_by) ?? User::query()->first();
                if ($actor === null) {
                    return;
                }

                $this->synchronizeMilestone->complete(
                    $instance->group,
                    'validation-survey-instrument',
                    $actor,
                    'official_form',
                    $instance->id,
                    'Instrument Validation Rating (RES-043B) completed.',
                );
                $completed++;
            });

        return compact('started', 'completed');
    }
}

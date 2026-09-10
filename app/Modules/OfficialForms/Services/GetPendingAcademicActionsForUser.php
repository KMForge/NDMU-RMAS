<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\DefenseEvaluationRound;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\ResearchProgress\Services\ResearchJourneyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class GetPendingAcademicActionsForUser
{
    public function __construct(private readonly ResearchJourneyService $journey) {}

    /** @return Collection<int, array<string, mixed>> */
    public function execute(User $user): Collection
    {
        $authorization = app(OfficialFormAuthorization::class);

        $visibleInstances = OfficialFormInstance::query()
            ->with(['definition', 'currentVersion.signatures', 'group.researchClass', 'group.members.student.studentProfile.program', 'researchClass', 'actorAssignments', 'titlePresentation.defense.activePanelAssignments'])
            ->get()
            ->filter(fn (OfficialFormInstance $instance) => Gate::forUser($user)->allows('view', $instance));

        $actions = collect();

        foreach ($visibleInstances as $instance) {
            $code = strtolower($instance->definition->code);
            $formSpec = OfficialResearchWorkflowRegistry::FORMS[$code] ?? null;

            if ($formSpec === null) {
                continue;
            }

            if ($instance->group !== null
                && in_array((int) $formSpec['stage'], $this->journey->automaticStageNumbersFor($instance->group), true)) {
                continue;
            }

            // Facilitator action on submitted RES-026
            if ($code === 'res-026' && in_array($instance->status, ['submitted', 'in_review'], true)) {
                $isFacilitator = (int) ($instance->researchClass?->facilitator_id ?? $instance->group?->researchClass?->facilitator_id ?? 0) === (int) $user->id;
                if ($isFacilitator && $user->can('defenses.manage')) {
                    $presentation = $instance->titlePresentation;
                    if ($presentation === null) {
                        $actions->push([
                            'id' => "form-{$instance->id}-schedule-title-presentation",
                            'form_code' => $code,
                            'form_title' => $formSpec['title'],
                            'instance_id' => $instance->id,
                            'group_id' => $instance->research_class_group_id,
                            'group_name' => $instance->group ? $instance->group->name : 'Unassigned Group',
                            'class_name' => $instance->group && $instance->group->researchClass ? $instance->group->researchClass->name : ($instance->researchClass ? $instance->researchClass->name : 'N/A'),
                            'stage' => $formSpec['stage'],
                            'stage_name' => $formSpec['stage_name'],
                            'academic_actor_type' => 'facilitator',
                            'actor_type_label' => 'Research Facilitator',
                            'action' => 'schedule_title_presentation',
                            'action_label' => 'Schedule Title Presentation',
                            'status' => $instance->status,
                            'route' => route('official-forms.workspace.show', $instance->id),
                            'created_at' => $instance->updated_at ? $instance->updated_at->diffForHumans() : 'Recently',
                        ]);
                    } elseif ($presentation->status === 'presented') {
                        $actions->push([
                            'id' => "form-{$instance->id}-record-title-result",
                            'form_code' => $code,
                            'form_title' => $formSpec['title'],
                            'instance_id' => $instance->id,
                            'group_id' => $instance->research_class_group_id,
                            'group_name' => $instance->group ? $instance->group->name : 'Unassigned Group',
                            'class_name' => $instance->group && $instance->group->researchClass ? $instance->group->researchClass->name : ($instance->researchClass ? $instance->researchClass->name : 'N/A'),
                            'stage' => $formSpec['stage'],
                            'stage_name' => $formSpec['stage_name'],
                            'academic_actor_type' => 'facilitator',
                            'actor_type_label' => 'Research Facilitator',
                            'action' => 'record_title_result',
                            'action_label' => 'Record Title Verdict',
                            'status' => $instance->status,
                            'route' => route('official-forms.workspace.show', $instance->id),
                            'created_at' => $instance->updated_at ? $instance->updated_at->diffForHumans() : 'Recently',
                        ]);
                    }
                }
            }

            foreach ($formSpec['actions'] as $actionKey => $actionSpec) {
                $permission = $actionSpec['permission'];
                $requiredActorType = $actionSpec['actor_type'];
                $fromStates = $actionSpec['from_states'];

                if ($code === 'res-036' && (int) $instance->initiated_by !== (int) $user->id) {
                    continue;
                }

                if (! in_array($instance->status, $fromStates, true)) {
                    continue;
                }

                if (! Gate::forUser($user)->allows($actionKey, $instance)) {
                    continue;
                }

                if ($instance->currentVersion?->signatures->contains(
                    fn ($signature): bool => (int) $signature->signer_user_id === (int) $user->id
                        && $signature->academic_action === $actionKey,
                )) {
                    continue;
                }

                $actorLabel = $this->resolveActorTypeLabel($requiredActorType);

                $actions->push([
                    'id' => "form-{$instance->id}-{$actionKey}",
                    'form_code' => $code,
                    'form_title' => $formSpec['title'],
                    'instance_id' => $instance->id,
                    'group_id' => $instance->research_class_group_id,
                    'group_name' => $instance->group ? $instance->group->name : 'Unassigned Group',
                    'class_name' => $instance->group && $instance->group->researchClass ? $instance->group->researchClass->name : ($instance->researchClass ? $instance->researchClass->name : 'N/A'),
                    'stage' => $formSpec['stage'],
                    'stage_name' => $formSpec['stage_name'],
                    'academic_actor_type' => $requiredActorType,
                    'actor_type_label' => $actorLabel,
                    'action' => $actionKey,
                    'action_label' => $actionSpec['label'],
                    'status' => $instance->status,
                    'route' => route('official-forms.workspace.show', $instance->id),
                    'created_at' => $instance->updated_at ? $instance->updated_at->diffForHumans() : 'Recently',
                ]);
            }
        }

        // Active defense evaluation rounds where the user is an assigned panelist and has not yet submitted
        $openRounds = DefenseEvaluationRound::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('roundPanelists', fn ($q) => $q->where('panelist_user_id', $user->id))
            ->with([
                'defense.group.researchClass',
                'defenseSchedule.room',
                'evaluations' => fn ($q) => $q->where('panelist_user_id', $user->id),
            ])
            ->get();

        foreach ($openRounds as $round) {
            $hasSubmitted = $round->evaluations->contains(fn ($e): bool => $e->status === 'submitted');
            if ($hasSubmitted) {
                continue;
            }

            // If user already has an in-progress draft RES-036 instance for this schedule, that instance is handled above
            $hasExistingDraft = $visibleInstances->contains(
                fn (OfficialFormInstance $inst): bool => strtolower($inst->definition?->code ?? '') === 'res-036'
                    && (int) $inst->initiated_by === (int) $user->id
                    && (int) $inst->source_id === (int) $round->defense_schedule_id
            );
            if ($hasExistingDraft) {
                continue;
            }

            $defense = $round->defense;
            $group = $defense?->group;
            $defenseTypeLabel = match ($defense?->defense_type) {
                'title_presentation' => 'Title Proposal',
                'proposal_defense' => 'Proposal Defense',
                'pre_final_defense' => 'Pre-Final Defense',
                'final_defense' => 'Final Oral Defense',
                default => 'Research Defense',
            };

            $stageNum = match ($defense?->defense_type) {
                'title_presentation' => 2,
                'proposal_defense' => 3,
                'pre_final_defense' => 5,
                'final_defense' => 6,
                default => 5,
            };

            $actions->push([
                'id' => "defense-round-{$round->id}-evaluate",
                'form_code' => 'res-036',
                'form_title' => 'Defense Evaluation Sheet (RES-036)',
                'instance_id' => null,
                'group_id' => $round->research_class_group_id,
                'group_name' => $group ? $group->name : 'Unassigned Group',
                'class_name' => $group && $group->researchClass ? $group->researchClass->name : 'N/A',
                'stage' => $stageNum,
                'stage_name' => $defenseTypeLabel,
                'academic_actor_type' => 'panelist',
                'actor_type_label' => 'Panel Member',
                'action' => 'evaluate',
                'action_label' => "Evaluate {$defenseTypeLabel} (RES-036)",
                'status' => 'Evaluation In Progress',
                'route' => route('official-forms.workspace.store-from-source', [
                    'definition' => 'res-036',
                    'sourceKind' => 'defense-schedule',
                    'source' => $round->defense_schedule_id,
                ]),
                'created_at' => $round->opened_at ? $round->opened_at->diffForHumans() : 'Recently',
            ]);
        }

        return $actions->values();
    }

    private function resolveActorTypeLabel(string $actorType): string
    {
        return match ($actorType) {
            'facilitator' => 'Research Facilitator',
            'program_coordinator' => 'Program Coordinator',
            'research_instructor' => 'Research Instructor',
            'adviser' => 'Thesis Adviser',
            'panelist' => 'Panel Member',
            'panel_chair' => 'Panel Chair',
            'title_panel_chairperson' => 'Title Panel Chairperson',
            'title_panel_member_1' => 'Title Panel Member 1',
            'title_panel_member_2' => 'Title Panel Member 2',
            'dean' => 'College Dean',
            'instrument_validator' => 'Instrument Validator',
            'language_editor' => 'Language Editor',
            'technical_editor' => 'Technical Editor',
            'specialist' => 'Subject Specialist',
            'student_researcher' => 'Student Researcher',
            default => 'Academic Officer',
        };
    }
}

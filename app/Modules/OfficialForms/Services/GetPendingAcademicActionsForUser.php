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
            ->with(['definition', 'currentVersion.signatures.verification', 'group.researchClass', 'group.members.student.studentProfile.program', 'researchClass', 'actorAssignments', 'titlePresentation.defense.activePanelAssignments'])
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

        DefenseEvaluationRound::query()
            ->with(['defense.group.researchClass'])
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('roundPanelists', fn ($query) => $query->where('panelist_user_id', $user->getKey()))
            ->whereDoesntHave('evaluations', fn ($query) => $query
                ->where('panelist_user_id', $user->getKey())
                ->where('status', 'submitted'))
            ->get()
            ->each(function (DefenseEvaluationRound $round) use ($actions): void {
                $group = $round->group;
                $defenseType = $round->defense?->defense_type;
                $stage = $defenseType === 'proposal_defense' ? 3 : ($defenseType === 'pre_final_defense' ? 10 : 11);

                $actions->push([
                    'id' => "defense-round-{$round->id}-evaluate",
                    'form_code' => 'res-036',
                    'form_title' => 'Evaluation of Research Defense',
                    'instance_id' => null,
                    'group_id' => $round->research_class_group_id,
                    'group_name' => $group?->name ?? 'Unassigned Group',
                    'class_name' => $group?->researchClass?->name ?? 'N/A',
                    'stage' => $stage,
                    'stage_name' => match ($defenseType) {
                        'proposal_defense' => 'Research Proposal Defense',
                        'pre_final_defense' => 'Research Pre-Final Defense',
                        default => 'Research Final/Oral Defense',
                    },
                    'academic_actor_type' => 'panelist',
                    'actor_type_label' => 'Panel Member',
                    'action' => 'evaluate',
                    'action_label' => 'Evaluate Defense',
                    'status' => $round->status,
                    'route' => route('panelist.evaluations.show', $round),
                    'created_at' => $round->updated_at?->diffForHumans() ?? 'Recently',
                ]);
            });

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

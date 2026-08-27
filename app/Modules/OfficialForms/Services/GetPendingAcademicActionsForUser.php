<?php

namespace App\Modules\OfficialForms\Services;

use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class GetPendingAcademicActionsForUser
{
    /** @return Collection<int, array<string, mixed>> */
    public function execute(User $user): Collection
    {
        $authorization = app(OfficialFormAuthorization::class);

        $visibleInstances = OfficialFormInstance::query()
            ->with(['definition', 'currentVersion.signatures', 'group.researchClass', 'researchClass', 'actorAssignments', 'titlePresentation.defense.activePanelAssignments'])
            ->get()
            ->filter(fn (OfficialFormInstance $instance) => Gate::forUser($user)->allows('view', $instance));

        $actions = collect();

        foreach ($visibleInstances as $instance) {
            $code = strtolower($instance->definition->code);
            $formSpec = OfficialResearchWorkflowRegistry::FORMS[$code] ?? null;

            if ($formSpec === null) {
                continue;
            }

            foreach ($formSpec['actions'] as $actionKey => $actionSpec) {
                $permission = $actionSpec['permission'];
                $requiredActorType = $actionSpec['actor_type'];
                $fromStates = $actionSpec['from_states'];

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

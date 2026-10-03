<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\ResearchGroupPanelMember;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignGroupDefenseCommittee
{
    public function __construct(
        private readonly CreateOfficialFormInstance $createOfficialForm,
        private readonly InstitutionalActorResolver $institutionalActors,
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    /**
     * Handle invocation with model instances.
     */
    public function handle(
        User $actor,
        ResearchClassGroup $group,
        string $defenseType,
        int $chairpersonId,
        array $panelUserIds,
        bool $isCustom = true
    ): ResearchGroupPanelCommittee {
        if (count($panelUserIds) < 2) {
            throw new \InvalidArgumentException('Exactly two panel members are required.');
        }

        return $this->execute(
            researchClassGroupId: $group->id,
            defenseType: $defenseType,
            chairpersonId: $chairpersonId,
            panelMember1Id: (int) $panelUserIds[0],
            panelMember2Id: (int) $panelUserIds[1],
            assignedByUserId: $actor->id,
            isCustom: $isCustom
        );
    }

    /**
     * Assign an individual customized or explicit committee to a specific research group.
     */
    public function execute(
        int $researchClassGroupId,
        string $defenseType,
        int $chairpersonId,
        int $panelMember1Id,
        int $panelMember2Id,
        int $assignedByUserId,
        bool $isCustom = true
    ): ResearchGroupPanelCommittee {
        // 1. Validate distinct evaluators
        if ($chairpersonId === $panelMember1Id || $chairpersonId === $panelMember2Id || $panelMember1Id === $panelMember2Id) {
            throw ValidationException::withMessages([
                'evaluators' => 'The Chairperson and two Panel Members must be three distinct faculty members.',
            ]);
        }

        // 2. Validate actor and group
        $actor = User::findOrFail($assignedByUserId);
        $group = ResearchClassGroup::with('researchClass')->findOrFail($researchClassGroupId);

        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense committees.');
        }

        if (! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('You are not authorized to manage committees for this class group.');
        }

        // 3. Validate candidate eligibility
        $allEvaluatorIds = array_unique([$chairpersonId, $panelMember1Id, $panelMember2Id]);
        $evaluators = User::whereIn('id', $allEvaluatorIds)->get();

        if ($evaluators->count() !== 3) {
            throw ValidationException::withMessages([
                'evaluators' => 'One or more selected committee evaluators could not be found.',
            ]);
        }

        foreach ($evaluators as $evaluator) {
            if ($evaluator->status !== AccountStatus::Active) {
                throw ValidationException::withMessages([
                    'evaluators' => "Faculty member {$evaluator->name} does not have an active account.",
                ]);
            }
        }

        $committee = DB::transaction(function () use (
            $group,
            $defenseType,
            $chairpersonId,
            $panelMember1Id,
            $panelMember2Id,
            $assignedByUserId,
            $isCustom
        ) {
            $groupCommittee = ResearchGroupPanelCommittee::updateOrCreate(
                [
                    'research_class_group_id' => $group->id,
                    'defense_type' => $defenseType,
                ],
                [
                    'chairperson_id' => $chairpersonId,
                    'is_custom' => $isCustom,
                    'created_by' => $assignedByUserId,
                    'updated_by' => $assignedByUserId,
                ]
            );

            // Delete existing members to prevent duplicate key violations when swapping or changing members
            ResearchGroupPanelMember::where('committee_id', $groupCommittee->id)->delete();

            ResearchGroupPanelMember::create([
                'committee_id' => $groupCommittee->id,
                'panel_position' => 'member_1',
                'user_id' => $panelMember1Id,
            ]);

            ResearchGroupPanelMember::create([
                'committee_id' => $groupCommittee->id,
                'panel_position' => 'member_2',
                'user_id' => $panelMember2Id,
            ]);

            return $groupCommittee->load('members.user', 'chairperson');
        });

        $this->issuePanelInvitations($group->fresh(['researchClass.facilitator.facultyProfile', 'members.student.studentProfile.program', 'researchGroup.currentProject']), $committee);

        return $committee->refresh()->load('members.user', 'chairperson');
    }

    private function issuePanelInvitations(ResearchClassGroup $group, ResearchGroupPanelCommittee $committee): void
    {
        $coordinator = $this->institutionalActors->programCoordinatorForGroup($group);
        if ($coordinator === null || ! $coordinator->can('classes.assign-advisers')) {
            return;
        }

        $slots = [
            'chairperson' => $committee->chairperson,
            'member_1' => $committee->members->firstWhere('panel_position', 'member_1')?->user,
            'member_2' => $committee->members->firstWhere('panel_position', 'member_2')?->user,
        ];

        foreach ($slots as $position => $invitee) {
            if ($invitee === null) {
                continue;
            }

            $contextKey = "panel-invitation:{$committee->defense_type}:{$position}";
            $existing = OfficialFormInstance::query()
                ->where('research_class_group_id', $group->id)
                ->where('context_key', $contextKey)
                ->whereHas('definition', fn ($query) => $query->where('code', 'RES-028'))
                ->whereHas('actorAssignments', fn ($query) => $query->where('user_id', $invitee->id)->where('status', 'active'))
                ->latest('id')
                ->first();

            if ($existing !== null) {
                continue;
            }

            OfficialFormInstance::query()
                ->where('research_class_group_id', $group->id)
                ->where('context_key', $contextKey)
                ->whereHas('definition', fn ($query) => $query->where('code', 'RES-028'))
                ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
                ->each(function ($instance): void {
                    $instance->update(['status' => 'cancelled']);
                    $instance->actorAssignments()->where('status', 'active')->update(['status' => 'inactive']);
                });

            $invitation = $this->createOfficialForm->handle(
                initiator: $coordinator,
                formCode: 'RES-028',
                groupId: $group->id,
                contextKey: $contextKey,
                actorUserId: $invitee->id,
                payload: [
                    'date' => now()->format('Y-m-d'),
                    'panel_role' => str($position)->headline()->toString(),
                    'defense' => str($committee->defense_type)->headline()->toString(),
                    'course' => (string) $group->researchClass?->name,
                    'research_title' => (string) ($group->researchGroup?->currentProject?->title ?? $group->name),
                ],
            );

            $this->notifications->send(
                recipient: $invitee,
                eventKey: 'panel.invitation.received',
                title: 'Panel invitation received',
                message: 'You were invited as '.str($position)->headline()." for {$group->name}.",
                category: 'defense',
                routeName: 'official-forms.workspace.show',
                routeParameters: ['instance' => $invitation->id],
                sourceType: ResearchGroupPanelCommittee::class,
                sourceId: $committee->id,
                actor: $coordinator,
                contextLabel: $group->name,
                actingAs: str($position)->headline()->toString(),
            );
        }
    }
}

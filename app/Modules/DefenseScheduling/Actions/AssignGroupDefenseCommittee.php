<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\ResearchGroupPanelMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignGroupDefenseCommittee
{
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

        return DB::transaction(function () use (
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

            ResearchGroupPanelMember::updateOrCreate(
                [
                    'committee_id' => $groupCommittee->id,
                    'panel_position' => 'member_1',
                ],
                [
                    'user_id' => $panelMember1Id,
                ]
            );

            ResearchGroupPanelMember::updateOrCreate(
                [
                    'committee_id' => $groupCommittee->id,
                    'panel_position' => 'member_2',
                ],
                [
                    'user_id' => $panelMember2Id,
                ]
            );

            return $groupCommittee->load('members.user', 'chairperson');
        });
    }
}

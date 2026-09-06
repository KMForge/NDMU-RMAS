<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\ResearchGroupPanelMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResetGroupDefenseCommittee
{
    /**
     * Handle invocation with model instances.
     */
    public function handle(
        User $actor,
        ResearchClassGroup $group,
        string $defenseType
    ): ResearchGroupPanelCommittee {
        return $this->execute(
            researchClassGroupId: $group->id,
            defenseType: $defenseType,
            assignedByUserId: $actor->id
        );
    }

    /**
     * Reset a customized group committee back to the class default committee.
     */
    public function execute(
        int $researchClassGroupId,
        string $defenseType,
        int $assignedByUserId
    ): ResearchGroupPanelCommittee {
        $actor = User::findOrFail($assignedByUserId);
        $group = ResearchClassGroup::with('researchClass')->findOrFail($researchClassGroupId);

        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense committees.');
        }

        if (! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('You are not authorized to manage committees for this class group.');
        }

        $classCommittee = ResearchClassPanelCommittee::with('members')
            ->where('research_class_id', $group->research_class_id)
            ->where('defense_type', $defenseType)
            ->first();

        if (! $classCommittee) {
            throw ValidationException::withMessages([
                'class_committee' => 'No class default committee exists for this defense stage.',
            ]);
        }

        return DB::transaction(function () use ($group, $defenseType, $classCommittee, $assignedByUserId) {
            $groupCommittee = ResearchGroupPanelCommittee::updateOrCreate(
                [
                    'research_class_group_id' => $group->id,
                    'defense_type' => $defenseType,
                ],
                [
                    'chairperson_id' => $classCommittee->chairperson_id,
                    'is_custom' => false,
                    'created_by' => $assignedByUserId,
                    'updated_by' => $assignedByUserId,
                ]
            );

            foreach ($classCommittee->members as $member) {
                ResearchGroupPanelMember::updateOrCreate(
                    [
                        'committee_id' => $groupCommittee->id,
                        'panel_position' => $member->panel_position,
                    ],
                    [
                        'user_id' => $member->user_id,
                    ]
                );
            }

            return $groupCommittee->load('members.user', 'chairperson');
        });
    }
}

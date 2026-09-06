<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchClassPanelMember;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\ResearchGroupPanelMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignClassDefenseCommittee
{
    /**
     * Handle invocation with model instances.
     */
    public function handle(
        User $actor,
        ResearchClass $researchClass,
        string $defenseType,
        int $chairpersonId,
        array $panelUserIds,
        array $groupIds = [],
        bool $overrideCustom = false
    ): ResearchClassPanelCommittee {
        if (count($panelUserIds) < 2) {
            throw new \InvalidArgumentException('Exactly two panel members are required.');
        }

        return $this->execute(
            researchClassId: $researchClass->id,
            defenseType: $defenseType,
            chairpersonId: $chairpersonId,
            panelMember1Id: (int) $panelUserIds[0],
            panelMember2Id: (int) $panelUserIds[1],
            assignedByUserId: $actor->id,
            applyToGroupIds: ! empty($groupIds) ? $groupIds : null,
            overwriteCustom: $overrideCustom
        );
    }

    /**
     * Assign a class-level default committee and propagate to class groups.
     *
     * @param  array<int>|null  $applyToGroupIds  If null, applies to all groups in the class
     * @param  bool  $overwriteCustom  If false, keeps customized group committees intact
     */
    public function execute(
        int $researchClassId,
        string $defenseType,
        int $chairpersonId,
        int $panelMember1Id,
        int $panelMember2Id,
        int $assignedByUserId,
        ?array $applyToGroupIds = null,
        bool $overwriteCustom = false
    ): ResearchClassPanelCommittee {
        // 1. Validate distinct evaluators
        $memberIds = [$panelMember1Id, $panelMember2Id];
        if ($chairpersonId === $panelMember1Id || $chairpersonId === $panelMember2Id || $panelMember1Id === $panelMember2Id) {
            throw ValidationException::withMessages([
                'evaluators' => 'The Chairperson and two Panel Members must be three distinct faculty members.',
            ]);
        }

        // 2. Validate actor
        $actor = User::findOrFail($assignedByUserId);
        $researchClass = ResearchClass::findOrFail($researchClassId);

        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense committees.');
        }

        if ((int) $researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('You are not authorized to manage committees for this class.');
        }

        // 3. Validate candidate eligibility (Faculty, Active, evaluations.create permission or Faculty role)
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
            $researchClass,
            $defenseType,
            $chairpersonId,
            $panelMember1Id,
            $panelMember2Id,
            $assignedByUserId,
            $applyToGroupIds,
            $overwriteCustom
        ) {
            // 4. Create or update class-level committee
            $committee = ResearchClassPanelCommittee::updateOrCreate(
                [
                    'research_class_id' => $researchClass->id,
                    'defense_type' => $defenseType,
                ],
                [
                    'chairperson_id' => $chairpersonId,
                    'created_by' => $assignedByUserId,
                    'updated_by' => $assignedByUserId,
                ]
            );

            // Sync class panel members
            ResearchClassPanelMember::updateOrCreate(
                [
                    'committee_id' => $committee->id,
                    'panel_position' => 'member_1',
                ],
                [
                    'user_id' => $panelMember1Id,
                ]
            );

            ResearchClassPanelMember::updateOrCreate(
                [
                    'committee_id' => $committee->id,
                    'panel_position' => 'member_2',
                ],
                [
                    'user_id' => $panelMember2Id,
                ]
            );

            // 5. Query groups in the class
            $groupsQuery = ResearchClassGroup::where('research_class_id', $researchClass->id)
                ->where('status', '!=', 'disbanded');

            if (! empty($applyToGroupIds)) {
                $groupsQuery->whereIn('id', $applyToGroupIds);
            }

            $groups = $groupsQuery->get();

            foreach ($groups as $group) {
                $existingGroupCommittee = ResearchGroupPanelCommittee::where('research_class_group_id', $group->id)
                    ->where('defense_type', $defenseType)
                    ->first();

                // If group has customized assignment and we are not overwriting custom, skip it!
                if ($existingGroupCommittee && $existingGroupCommittee->is_custom && ! $overwriteCustom) {
                    continue;
                }

                $groupCommittee = ResearchGroupPanelCommittee::updateOrCreate(
                    [
                        'research_class_group_id' => $group->id,
                        'defense_type' => $defenseType,
                    ],
                    [
                        'chairperson_id' => $chairpersonId,
                        'is_custom' => false,
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
            }

            return $committee->load('members.user', 'chairperson');
        });
    }
}

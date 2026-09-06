<?php

namespace App\Modules\DefenseScheduling\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassPanelCommittee;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use Carbon\CarbonInterface;

class CheckDefenseSchedulingConflicts
{
    /**
     * @param  array<int, int>  $orderedGroupIds  Array of group IDs in presentation order
     * @return array{
     *     has_conflicts: bool,
     *     conflicts: array<int, string>,
     *     warnings: array<int, string>,
     *     group_committees: array<int, array>
     * }
     */
    public function check(
        ResearchClass $researchClass,
        string $defenseType,
        int $roomId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        array $orderedGroupIds,
        ?int $excludeSessionId = null,
    ): array {
        $conflicts = [];
        $warnings = [];
        $groupCommittees = [];

        // 1. Basic time validation
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $conflicts[] = 'Session end time must be strictly after session start time.';
        }

        // 2. Room check
        $room = DefenseRoom::find($roomId);
        if (! $room) {
            $conflicts[] = 'The selected defense venue does not exist.';
        } elseif (! $room->is_active) {
            $conflicts[] = "Defense venue {$room->code} ({$room->name}) is inactive and cannot be scheduled.";
        } else {
            // Room conflict: another current schedule overlapping with [startsAt, endsAt]
            $roomQuery = DefenseSchedule::query()
                ->where('room_id', $roomId)
                ->where('status', 'current')
                ->whereHas('defense', fn ($q) => $q->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']))
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt);

            if ($excludeSessionId !== null) {
                $roomQuery->where(function ($q) use ($excludeSessionId) {
                    $q->whereNull('defense_session_id')
                        ->orWhere('defense_session_id', '!=', $excludeSessionId);
                });
            }

            $roomConflict = $roomQuery->with(['defense.group', 'scheduledBy'])->first();
            if ($roomConflict !== null) {
                $conflictGroup = $roomConflict->defense?->group?->name ?? 'another defense';
                $timeSlot = $roomConflict->starts_at?->format('h:i A').' - '.$roomConflict->ends_at?->format('h:i A');
                $conflicts[] = "Venue {$room->name} is already booked for {$conflictGroup} during {$timeSlot}.";
            }
        }

        // 3. Duplicate groups in batch
        if (count($orderedGroupIds) !== count(array_unique($orderedGroupIds))) {
            $conflicts[] = 'Duplicate research groups detected in the scheduling batch.';
        }

        // 4. Groups validation & Committee completeness
        $classGroups = ResearchClassGroup::query()
            ->where('research_class_id', $researchClass->id)
            ->whereIn('id', $orderedGroupIds)
            ->with(['adviser', 'researchClass'])
            ->get()
            ->keyBy('id');

        $classCommittee = ResearchClassPanelCommittee::query()
            ->where('research_class_id', $researchClass->id)
            ->where('defense_type', $defenseType)
            ->with(['chairperson', 'members.user'])
            ->first();

        $savedGroupCommittees = ResearchGroupPanelCommittee::query()
            ->whereIn('research_class_group_id', $orderedGroupIds)
            ->where('defense_type', $defenseType)
            ->with(['chairperson', 'members.user'])
            ->get()
            ->keyBy('research_class_group_id');

        $involvedFacultyIds = [];

        foreach ($orderedGroupIds as $index => $groupId) {
            $group = $classGroups->get($groupId);
            $orderNum = $index + 1;

            if ($group === null) {
                $conflicts[] = "Group #{$groupId} does not belong to class {$researchClass->name}.";

                continue;
            }

            if ($group->status !== 'active') {
                $conflicts[] = "Group {$group->name} is not in active status ({$group->status}).";
            }

            // Group overlap check with other defenses
            $groupConflictQuery = DefenseSchedule::query()
                ->where('status', 'current')
                ->whereHas('defense', function ($q) use ($groupId) {
                    $q->where('research_class_group_id', $groupId)
                        ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
                })
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt);

            if ($excludeSessionId !== null) {
                $groupConflictQuery->where(function ($q) use ($excludeSessionId) {
                    $q->whereNull('defense_session_id')
                        ->orWhere('defense_session_id', '!=', $excludeSessionId);
                });
            }

            if ($groupConflictQuery->exists()) {
                $conflicts[] = "Group {$group->name} already has an active defense schedule during this session time.";
            }

            // Check if aggregate already exists for this stage
            $existingDefense = Defense::query()
                ->where('research_class_group_id', $groupId)
                ->where('defense_type', $defenseType)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->first();

            if ($existingDefense !== null && ($excludeSessionId === null || $existingDefense->currentSchedule?->defense_session_id !== $excludeSessionId)) {
                $conflicts[] = "Group {$group->name} already has an active {$defenseType} scheduled.";
            }

            // Committee assignment check
            $committee = $savedGroupCommittees->get($groupId);
            $chairperson = $committee?->chairperson ?? $classCommittee?->chairperson;
            $members = $committee?->members ?? $classCommittee?->members ?? collect();
            $member1 = $members->firstWhere('panel_position', 'member_1')?->user ?? $members->get(0)?->user ?? null;
            $member2 = $members->firstWhere('panel_position', 'member_2')?->user ?? $members->get(1)?->user ?? null;

            $isCustom = $committee?->is_custom ?? false;
            $statusLabel = $committee !== null
                ? ($isCustom ? 'Customized assignment' : 'Uses class assignment')
                : ($classCommittee !== null ? 'Uses class assignment' : 'Missing assignment');

            $groupCommittees[$groupId] = [
                'order' => $orderNum,
                'group_id' => $groupId,
                'group_name' => $group->name,
                'status_label' => $statusLabel,
                'is_complete' => ($chairperson !== null && $member1 !== null && $member2 !== null),
                'chairperson' => $chairperson ? ['id' => $chairperson->id, 'name' => $chairperson->name] : null,
                'member_1' => $member1 ? ['id' => $member1->id, 'name' => $member1->name] : null,
                'member_2' => $member2 ? ['id' => $member2->id, 'name' => $member2->name] : null,
            ];

            if ($chairperson === null || $member1 === null || $member2 === null) {
                $missingRoles = [];
                if ($chairperson === null) {
                    $missingRoles[] = 'Chairperson';
                }
                if ($member1 === null) {
                    $missingRoles[] = 'Panel Member 1';
                }
                if ($member2 === null) {
                    $missingRoles[] = 'Panel Member 2';
                }
                $conflicts[] = "Group {$group->name} (Order #{$orderNum}) has incomplete committee assignment: Missing ".implode(', ', $missingRoles).'.';
            } else {
                // Ensure distinct faculty in this committee
                $committeeIds = [$chairperson->id, $member1->id, $member2->id];
                if (count(array_unique($committeeIds)) !== 3) {
                    $conflicts[] = "Group {$group->name} (Order #{$orderNum}) has duplicate faculty assigned to its committee.";
                }

                foreach ($committeeIds as $fId) {
                    $involvedFacultyIds[$fId][] = "Group {$group->name} (Order #{$orderNum})";
                }
            }
        }

        // 5. Check if any involved faculty has a conflicting defense in ANOTHER room outside this session
        if (! empty($involvedFacultyIds)) {
            $uniqueFacultyIds = array_keys($involvedFacultyIds);

            // Verify active faculty
            $inactiveFaculty = User::query()
                ->whereIn('id', $uniqueFacultyIds)
                ->where(function ($q) {
                    $q->where('user_type', '!=', UserType::Faculty)
                        ->orWhere('status', '!=', AccountStatus::Active)
                        ->orWhereNull('approved_at')
                        ->orWhereNull('email_verified_at');
                })
                ->get();

            foreach ($inactiveFaculty as $inact) {
                $conflicts[] = "Faculty {$inact->name} is inactive or unverified and cannot serve on the defense committee.";
            }

            // Outside panelist conflict check
            $panelConflictQuery = DefensePanelAssignment::query()
                ->whereIn('user_id', $uniqueFacultyIds)
                ->whereNull('ended_at')
                ->whereHas('defense', fn ($q) => $q->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']))
                ->whereHas('defense.currentSchedule', function ($q) use ($startsAt, $endsAt, $excludeSessionId) {
                    $q->where('status', 'current')
                        ->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);

                    if ($excludeSessionId !== null) {
                        $q->where(function ($sub) use ($excludeSessionId) {
                            $sub->whereNull('defense_session_id')
                                ->orWhere('defense_session_id', '!=', $excludeSessionId);
                        });
                    }
                })
                ->with(['user', 'defense.group', 'defense.currentSchedule.room']);

            $conflictingAssignments = $panelConflictQuery->get();

            foreach ($conflictingAssignments as $assign) {
                $facultyName = $assign->user?->name ?? 'Faculty member';
                $otherGroup = $assign->defense?->group?->name ?? 'another group';
                $otherRoom = $assign->defense?->currentSchedule?->room?->name ?? 'another room';
                $otherTime = $assign->defense?->currentSchedule?->starts_at?->format('h:i A').' - '.$assign->defense?->currentSchedule?->ends_at?->format('h:i A');

                $conflicts[] = "Faculty {$facultyName} has an active defense conflict with {$otherGroup} in {$otherRoom} during {$otherTime}.";
            }
        }

        return [
            'has_conflicts' => ! empty($conflicts),
            'conflicts' => array_values(array_unique($conflicts)),
            'warnings' => array_values(array_unique($warnings)),
            'group_committees' => $groupCommittees,
        ];
    }
}

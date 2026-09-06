<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\DefenseSession;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkScheduleDefenses
{
    /**
     * Handle invocation from controller or jobs using model instances.
     */
    public function handle(
        User $actor,
        ResearchClass $researchClass,
        string $defenseType,
        int $roomId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        array $orderedGroupIds,
        ?string $notes = null
    ): DefenseSession {
        return $this->execute(
            researchClassId: $researchClass->id,
            defenseType: $defenseType,
            roomId: $roomId,
            sessionDate: $startsAt->toDateString(),
            startsAt: $startsAt->toDateTimeString(),
            endsAt: $endsAt->toDateTimeString(),
            orderedGroupIds: $orderedGroupIds,
            scheduledByUserId: $actor->id,
            notes: $notes
        );
    }

    /**
     * Bulk schedules multiple research groups within a shared session window (e.g. 7:00 AM to 6:00 PM)
     * with an arranged presentation sequence order (1, 2, 3...).
     *
     * @param  string  $sessionDate  Y-m-d
     * @param  string  $startsAt  Y-m-d H:i:s or ISO datetime
     * @param  string  $endsAt  Y-m-d H:i:s or ISO datetime
     * @param  array<int>  $orderedGroupIds  Array of group IDs in presentation order (index 0 is order #1)
     */
    public function execute(
        int $researchClassId,
        string $defenseType,
        int $roomId,
        string $sessionDate,
        string $startsAt,
        string $endsAt,
        array $orderedGroupIds,
        int $scheduledByUserId,
        ?string $notes = null
    ): DefenseSession {
        $start = Carbon::parse($startsAt);
        $end = Carbon::parse($endsAt);

        if ($start->gte($end)) {
            throw ValidationException::withMessages([
                'session_window' => 'Defense session start time must be strictly before end time.',
            ]);
        }

        if (empty($orderedGroupIds)) {
            throw ValidationException::withMessages([
                'ordered_groups' => 'Please select at least one research group to schedule.',
            ]);
        }

        // 1. Validate actor and class ownership
        $actor = User::findOrFail($scheduledByUserId);
        $researchClass = ResearchClass::findOrFail($researchClassId);

        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to schedule defenses.');
        }

        if ((int) $researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('You are not authorized to schedule defenses for this research class.');
        }

        // 2. Validate Room
        $room = DefenseRoom::where('id', $roomId)->where('is_active', true)->first();
        if (! $room) {
            throw ValidationException::withMessages([
                'room_id' => 'The selected defense venue is invalid or inactive.',
            ]);
        }

        // 3. Validate Groups and verify all have assigned committees
        $groups = ResearchClassGroup::with(['panelCommittees' => function ($q) use ($defenseType) {
            $q->where('defense_type', $defenseType)->with('members.user', 'chairperson');
        }])
            ->where('research_class_id', $researchClass->id)
            ->whereIn('id', $orderedGroupIds)
            ->get()
            ->keyBy('id');

        if ($groups->count() !== count($orderedGroupIds)) {
            throw ValidationException::withMessages([
                'ordered_groups' => 'One or more selected research groups do not belong to this class.',
            ]);
        }

        $allCommitteeEvaluators = [];
        $groupCommittees = [];

        foreach ($orderedGroupIds as $groupId) {
            $group = $groups->get($groupId);
            $committee = $group->panelCommittees->first();

            if (! $committee || ! $committee->chairperson_id || $committee->members->count() < 2) {
                throw ValidationException::withMessages([
                    'committees' => "Research group '{$group->name}' does not have a complete committee assigned for {$defenseType}. Please assign a Chairperson and two Panelists before scheduling.",
                ]);
            }

            $groupCommittees[$groupId] = $committee;

            $allCommitteeEvaluators[$committee->chairperson_id] = true;
            foreach ($committee->members as $member) {
                $allCommitteeEvaluators[$member->user_id] = true;
            }
        }

        // 4. Conflict Detection
        // 4a. Room conflict: Check if the room has an overlapping schedule outside this batch
        $roomConflict = DefenseSchedule::where('room_id', $roomId)
            ->where('status', 'current')
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->whereDoesntHave('defense', function ($q) use ($orderedGroupIds) {
                $q->whereIn('research_class_group_id', $orderedGroupIds);
            })
            ->exists();

        if ($roomConflict) {
            throw ValidationException::withMessages([
                'room_conflict' => "Venue '{$room->name}' is already booked for another defense session during {$start->format('g:i A')} - {$end->format('g:i A')}.",
            ]);
        }

        // 4b. Panelists conflict: Check if any panelist is booked in another defense room during this time window
        $evaluatorUserIds = array_keys($allCommitteeEvaluators);
        $panelConflicts = DefensePanelAssignment::whereIn('user_id', $evaluatorUserIds)
            ->whereNull('ended_at')
            ->whereHas('defense.currentSchedule', function ($q) use ($start, $end, $roomId, $orderedGroupIds) {
                $q->where('status', 'current')
                    ->where('room_id', '!=', $roomId)
                    ->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start)
                    ->whereDoesntHave('defense', function ($sub) use ($orderedGroupIds) {
                        $sub->whereIn('research_class_group_id', $orderedGroupIds);
                    });
            })
            ->with('user')
            ->get();

        if ($panelConflicts->isNotEmpty()) {
            $conflictingNames = $panelConflicts->pluck('user.name')->unique()->join(', ');
            throw ValidationException::withMessages([
                'panel_conflict' => "The following committee member(s) have a scheduling conflict in another venue: {$conflictingNames}.",
            ]);
        }

        // 5. Transactional Execution
        return DB::transaction(function () use (
            $actor,
            $researchClass,
            $defenseType,
            $roomId,
            $sessionDate,
            $start,
            $end,
            $orderedGroupIds,
            $groupCommittees,
            $scheduledByUserId,
            $notes
        ) {
            // Create DefenseSession
            $session = DefenseSession::create([
                'research_class_id' => $researchClass->id,
                'defense_type' => $defenseType,
                'room_id' => $roomId,
                'session_date' => $sessionDate,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'current',
                'scheduled_by' => $scheduledByUserId,
                'notes' => $notes,
            ]);

            $orderIndex = 1;

            foreach ($orderedGroupIds as $groupId) {
                $committee = $groupCommittees[$groupId];

                // Find or create Defense record for this group and defense stage
                $defense = Defense::firstOrCreate(
                    [
                        'research_class_group_id' => $groupId,
                        'defense_type' => $defenseType,
                    ],
                    [
                        'status' => 'scheduled',
                        'created_by' => $scheduledByUserId,
                    ]
                );

                // Mark any previous active schedule as superseded
                if ($defense->current_schedule_id) {
                    DefenseSchedule::where('id', $defense->current_schedule_id)
                        ->update(['status' => 'superseded']);
                }

                // Create DefenseSchedule with shared session window and presentation order
                $schedule = DefenseSchedule::create([
                    'defense_id' => $defense->id,
                    'defense_session_id' => $session->id,
                    'presentation_order' => $orderIndex,
                    'room_id' => $roomId,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'status' => 'current',
                    'scheduled_by' => $scheduledByUserId,
                    'reason' => "Bulk scheduled for session on {$sessionDate} (Order #{$orderIndex})",
                ]);

                $defense->update([
                    'current_schedule_id' => $schedule->id,
                    'status' => 'scheduled',
                ]);

                // End previous panel assignments
                DefensePanelAssignment::where('defense_id', $defense->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now()]);

                // Assign Chairperson
                DefensePanelAssignment::create([
                    'defense_id' => $defense->id,
                    'user_id' => $committee->chairperson_id,
                    'panel_position' => 'chairperson',
                    'assigned_by' => $scheduledByUserId,
                    'assigned_at' => now(),
                ]);

                // Assign Panel Members
                $member1 = $committee->member1();
                if ($member1) {
                    DefensePanelAssignment::create([
                        'defense_id' => $defense->id,
                        'user_id' => $member1->user_id,
                        'panel_position' => 'member_1',
                        'assigned_by' => $scheduledByUserId,
                        'assigned_at' => now(),
                    ]);
                }

                $member2 = $committee->member2();
                if ($member2) {
                    DefensePanelAssignment::create([
                        'defense_id' => $defense->id,
                        'user_id' => $member2->user_id,
                        'panel_position' => 'member_2',
                        'assigned_by' => $scheduledByUserId,
                        'assigned_at' => now(),
                    ]);
                }

                $orderIndex++;
            }

            // Audit log
            AuditLog::create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'defense.scheduled.bulk',
                'auditable_type' => DefenseSession::class,
                'auditable_id' => $session->id,
                'description' => 'Bulk scheduled '.count($orderedGroupIds)." defense groups for session #{$session->id} on {$sessionDate}.",
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'System',
            ]);

            return $session->load(['schedules.defense.group', 'room']);
        });
    }
}

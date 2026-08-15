<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleDefense
{
    public function handle(
        User $actor,
        ResearchClassGroup $group,
        string $defenseType,
        int $roomId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        array $panelUserIds
    ): Defense {
        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense schedules.');
        }

        if (! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You do not own the research class for this group.');
        }

        if (! in_array($defenseType, ['proposal_defense', 'final_defense'], true)) {
            throw new InvalidArgumentException("Invalid defense type: {$defenseType}.");
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('End time must be strictly after start time.');
        }

        if (array_values(array_unique($panelUserIds)) !== array_values($panelUserIds)) {
            throw new InvalidArgumentException('Duplicate panel user IDs in request.');
        }

        return DB::transaction(function () use ($actor, $group, $defenseType, $roomId, $startsAt, $endsAt, $panelUserIds) {
            // 1. Lock Group
            $lockedGroup = ResearchClassGroup::where('id', $group->id)->lockForUpdate()->firstOrFail();

            if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
                throw new AuthorizationException('Unauthorized to manage defense schedules.');
            }

            if (! $lockedGroup->researchClass || (int) $lockedGroup->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this group.');
            }

            // Fail-Closed Initial Defense Rule: Only 1 initial defense aggregate per group and defense type
            $hasHistorical = Defense::where('research_class_group_id', $lockedGroup->id)
                ->where('defense_type', $defenseType)
                ->exists();

            if ($hasHistorical) {
                throw new InvalidArgumentException("A defense aggregate for group {$lockedGroup->id} and type {$defenseType} already exists.");
            }

            // 2. Lock Room
            $room = DefenseRoom::where('id', $roomId)->lockForUpdate()->firstOrFail();
            if (! $room->is_active) {
                throw new InvalidArgumentException("Defense room {$room->code} is inactive and cannot be scheduled.");
            }

            // 3. Lock Panel Users sorted by user ID ASC
            sort($panelUserIds);
            $panelUsers = User::whereIn('id', $panelUserIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($panelUsers->count() !== count($panelUserIds)) {
                throw new InvalidArgumentException('One or more panel user IDs were not found.');
            }

            foreach ($panelUsers as $u) {
                if ($u->user_type !== UserType::Faculty || $u->status !== AccountStatus::Active || $u->approved_at === null || $u->email_verified_at === null || ! $u->can('evaluations.create')) {
                    throw new InvalidArgumentException("User {$u->id} is not an eligible Defense Panel candidate.");
                }
            }

            // 4. Overlap Checks (Half-open: existing.starts_at < requested_ends AND existing.ends_at > requested_starts)
            // Group conflict
            $groupConflict = DefenseSchedule::whereHas('defense', function ($q) use ($lockedGroup) {
                $q->where('research_class_group_id', $lockedGroup->id);
            })
                ->where('status', 'current')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($groupConflict) {
                throw new InvalidArgumentException('Group already has a defense schedule during the requested time interval.');
            }

            // Room conflict
            $roomConflict = DefenseSchedule::where('room_id', $roomId)
                ->where('status', 'current')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($roomConflict) {
                throw new InvalidArgumentException('Room already has a defense schedule during the requested time interval.');
            }

            // Panelist conflict
            $panelConflict = DefensePanelAssignment::whereIn('user_id', $panelUserIds)
                ->whereNull('ended_at')
                ->whereHas('defense.currentSchedule', function ($q) use ($startsAt, $endsAt) {
                    $q->where('status', 'current')
                        ->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);
                })
                ->exists();

            if ($panelConflict) {
                throw new InvalidArgumentException('One or more panel members have a schedule conflict during the requested time interval.');
            }

            // 5. Create Defense
            $defense = Defense::create([
                'research_class_group_id' => $lockedGroup->id,
                'defense_type' => $defenseType,
                'status' => 'scheduled',
                'created_by' => $actor->id,
            ]);

            // 6. Create DefenseSchedule
            $schedule = DefenseSchedule::create([
                'defense_id' => $defense->id,
                'room_id' => $roomId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'current',
                'scheduled_by' => $actor->id,
            ]);

            $defense->current_schedule_id = $schedule->id;
            $defense->save();

            // 7. Create Active Panel Assignments
            foreach ($panelUserIds as $uId) {
                DefensePanelAssignment::create([
                    'defense_id' => $defense->id,
                    'user_id' => $uId,
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                ]);
            }

            // 8. Audit Log
            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'defense.scheduled',
                'auditable_type' => Defense::class,
                'auditable_id' => $defense->id,
                'description' => "Scheduled defense #{$defense->id} for group #{$defense->research_class_group_id}.",
                'subject_snapshot' => [
                    'starts_at' => $startsAt->toIso8601String(),
                    'ends_at' => $endsAt->toIso8601String(),
                    'room_id' => $roomId,
                    'panel_user_ids' => $panelUserIds,
                ],
            ]);

            return $defense->fresh(['currentSchedule.room', 'activePanelAssignments.user']);
        });
    }
}

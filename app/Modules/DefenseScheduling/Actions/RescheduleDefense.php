<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RescheduleDefense
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    public function handle(
        User $actor,
        Defense $defense,
        int $expectedCurrentScheduleId,
        int $newRoomId,
        CarbonInterface $newStartsAt,
        CarbonInterface $newEndsAt,
        string $reason
    ): DefenseSchedule {
        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense schedules.');
        }

        $group = $defense->group;
        if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
        }

        if ($newEndsAt->lessThanOrEqualTo($newStartsAt)) {
            throw new InvalidArgumentException('End time must be strictly after start time.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A valid reason is required for rescheduling.');
        }

        return DB::transaction(function () use ($actor, $defense, $expectedCurrentScheduleId, $newRoomId, $newStartsAt, $newEndsAt, $reason) {
            // 1. Lock Group & Defense
            $lockedGroup = ResearchClassGroup::where('id', $defense->research_class_group_id)->lockForUpdate()->firstOrFail();
            $lockedDefense = Defense::where('id', $defense->id)->lockForUpdate()->firstOrFail();

            if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
                throw new AuthorizationException('Unauthorized to manage defense schedules.');
            }

            if (! $lockedGroup->researchClass || (int) $lockedGroup->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
            }

            if (in_array($lockedDefense->status, ['cancelled', 'completed'], true) || $lockedDefense->current_schedule_id === null) {
                throw new InvalidArgumentException("Cannot reschedule a {$lockedDefense->status} defense.");
            }

            $activeRound = DefenseEvaluationRound::query()
                ->where('defense_id', $lockedDefense->id)
                ->whereIn('status', ['open', 'in_progress', 'complete', 'finalized', 'released'])
                ->first();

            if ($activeRound) {
                throw new InvalidArgumentException('Cannot reschedule defense: An evaluation round exists for this defense.');
            }

            if ((int) $lockedDefense->current_schedule_id !== (int) $expectedCurrentScheduleId) {
                throw new InvalidArgumentException('Stale schedule reference: Current defense schedule has changed.');
            }

            $oldSchedule = DefenseSchedule::where('id', $expectedCurrentScheduleId)->lockForUpdate()->firstOrFail();
            if ((int) $oldSchedule->defense_id !== (int) $lockedDefense->id) {
                throw new InvalidArgumentException('Schedule does not belong to specified defense.');
            }

            // 2. Lock Rooms sorted by ID ASC
            $roomIds = array_values(array_unique([$oldSchedule->room_id, $newRoomId]));
            sort($roomIds);
            $rooms = DefenseRoom::whereIn('id', $roomIds)->orderBy('id', 'asc')->lockForUpdate()->get();
            $newRoom = $rooms->firstWhere('id', $newRoomId);

            if (! $newRoom || ! $newRoom->is_active) {
                throw new InvalidArgumentException('Selected new room is inactive or invalid.');
            }

            // 3. Lock Panel Members sorted by ID ASC
            $panelUserIds = DefensePanelAssignment::where('defense_id', $lockedDefense->id)
                ->whereNull('ended_at')
                ->pluck('user_id')
                ->sort()
                ->values()
                ->toArray();

            if (! empty($panelUserIds)) {
                User::whereIn('id', $panelUserIds)->orderBy('id', 'asc')->lockForUpdate()->get();
            }

            // 4. Overlap Checks excluding current schedule ID
            // Group conflict
            $groupConflict = DefenseSchedule::whereHas('defense', function ($q) use ($lockedGroup) {
                $q->where('research_class_group_id', $lockedGroup->id)
                    ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
            })
                ->where('id', '!=', $oldSchedule->id)
                ->where('status', 'current')
                ->where('starts_at', '<', $newEndsAt)
                ->where('ends_at', '>', $newStartsAt)
                ->exists();

            if ($groupConflict) {
                throw new InvalidArgumentException('Group already has another active defense schedule during the requested time interval.');
            }

            // Room conflict
            $roomConflict = DefenseSchedule::where('room_id', $newRoomId)
                ->whereHas('defense', function ($q) {
                    $q->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
                })
                ->where('id', '!=', $oldSchedule->id)
                ->where('status', 'current')
                ->where('starts_at', '<', $newEndsAt)
                ->where('ends_at', '>', $newStartsAt)
                ->exists();

            if ($roomConflict) {
                throw new InvalidArgumentException('Room already has an active defense schedule during the requested time interval.');
            }

            // Panelist conflict
            if (! empty($panelUserIds)) {
                $panelConflict = DefensePanelAssignment::whereIn('user_id', $panelUserIds)
                    ->whereNull('ended_at')
                    ->where('defense_id', '!=', $lockedDefense->id)
                    ->whereHas('defense', function ($q) {
                        $q->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
                    })
                    ->whereHas('defense.currentSchedule', function ($q) use ($oldSchedule, $newStartsAt, $newEndsAt) {
                        $q->where('id', '!=', $oldSchedule->id)
                            ->where('status', 'current')
                            ->where('starts_at', '<', $newEndsAt)
                            ->where('ends_at', '>', $newStartsAt);
                    })
                    ->exists();

                if ($panelConflict) {
                    throw new InvalidArgumentException('One or more assigned panel members have an active schedule conflict during the requested time interval.');
                }
            }

            // 5. Create new Schedule S2
            $newSchedule = DefenseSchedule::create([
                'defense_id' => $lockedDefense->id,
                'room_id' => $newRoomId,
                'starts_at' => $newStartsAt,
                'ends_at' => $newEndsAt,
                'status' => 'current',
                'scheduled_by' => $actor->id,
                'reason' => $reason,
                'supersedes_schedule_id' => $oldSchedule->id,
            ]);

            // 6. Mark old schedule S1 as superseded
            $oldSchedule->status = 'superseded';
            $oldSchedule->reason = $reason;
            $oldSchedule->save();

            // 7. Update defense pointer
            $lockedDefense->current_schedule_id = $newSchedule->id;
            $lockedDefense->save();

            // 8. Audit Log
            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'defense.rescheduled',
                'auditable_type' => Defense::class,
                'auditable_id' => $lockedDefense->id,
                'description' => "Rescheduled defense #{$lockedDefense->id}.",
                'subject_snapshot' => [
                    'old_schedule_id' => $oldSchedule->id,
                    'new_schedule_id' => $newSchedule->id,
                    'starts_at' => $newStartsAt->toIso8601String(),
                    'ends_at' => $newEndsAt->toIso8601String(),
                    'room_id' => $newRoomId,
                    'reason' => $reason,
                ],
            ]);

            $students = $lockedGroup->members()->with('student')->get()->pluck('student')->filter();
            $this->notifications->sendToMany(
                recipients: $students,
                eventKey: 'defense.rescheduled',
                title: 'Defense schedule changed',
                message: "Your defense was rescheduled to {$newStartsAt->format('M j, Y g:i A')}.",
                category: 'defense',
                routeName: 'student.dashboard',
                routeParameters: ['tab' => 'defense'],
                sourceType: DefenseSchedule::class,
                sourceId: $newSchedule->getKey(),
                actor: $actor,
                contextLabel: $lockedGroup->name,
                actingAs: 'Student Researcher',
            );

            $panelists = User::query()->whereIn('id', $panelUserIds)->get();
            $this->notifications->sendToMany(
                recipients: $panelists,
                eventKey: 'defense.rescheduled',
                title: 'Assigned defense rescheduled',
                message: "{$lockedGroup->name}'s defense was rescheduled to {$newStartsAt->format('M j, Y g:i A')}.",
                category: 'defense',
                routeName: 'panelist.dashboard',
                routeParameters: ['tab' => 'schedule'],
                sourceType: DefenseSchedule::class,
                sourceId: $newSchedule->getKey(),
                actor: $actor,
                contextLabel: $lockedGroup->name,
                actingAs: 'Panel Member',
            );

            return $newSchedule->fresh(['room', 'defense']);
        });
    }
}

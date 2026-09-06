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
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ScheduleDefense
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    public function handle(
        User $actor,
        ResearchClassGroup $group,
        string $defenseType,
        int $roomId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        array $panelUserIds,
        ?int $chairpersonUserId = null,
    ): Defense {
        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense schedules.');
        }

        if (! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You do not own the research class for this group.');
        }

        if (! in_array($defenseType, ['title_presentation', 'proposal_defense', 'pre_final_defense', 'final_defense'], true)) {
            throw new InvalidArgumentException("Invalid defense type: {$defenseType}.");
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new InvalidArgumentException('End time must be strictly after start time.');
        }

        if (array_values(array_unique($panelUserIds)) !== array_values($panelUserIds)) {
            throw new InvalidArgumentException('Duplicate panel user IDs in request.');
        }

        if ($chairpersonUserId === null || empty($panelUserIds)) {
            $groupCommittee = ResearchGroupPanelCommittee::with('members')
                ->where('research_class_group_id', $group->id)
                ->where('defense_type', $defenseType)
                ->first();
            if ($groupCommittee) {
                $chairpersonUserId = $chairpersonUserId ?? $groupCommittee->chairperson_id;
                if (empty($panelUserIds)) {
                    $panelUserIds = $groupCommittee->members->pluck('user_id')->all();
                }
            }
        }

        if ($chairpersonUserId !== null) {
            if (count($panelUserIds) !== 2 || in_array($chairpersonUserId, $panelUserIds, true)) {
                throw new InvalidArgumentException('The Chairperson and two Panel Members must be three distinct Faculty users.');
            }
        }

        return DB::transaction(function () use ($actor, $group, $defenseType, $roomId, $startsAt, $endsAt, $panelUserIds, $chairpersonUserId) {
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
            $assignedUserIds = $panelUserIds;
            if ($chairpersonUserId !== null) {
                $assignedUserIds[] = $chairpersonUserId;
            }
            $assignedUserIds = array_values(array_unique($assignedUserIds));
            sort($assignedUserIds);
            $panelUsers = User::whereIn('id', $assignedUserIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($panelUsers->count() !== count($assignedUserIds)) {
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
                $q->where('research_class_group_id', $lockedGroup->id)
                    ->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
            })
                ->where('status', 'current')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($groupConflict) {
                throw new InvalidArgumentException('Group already has an active defense schedule during the requested time interval.');
            }

            // Room conflict
            $roomConflict = DefenseSchedule::where('room_id', $roomId)
                ->whereHas('defense', function ($q) {
                    $q->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
                })
                ->where('status', 'current')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($roomConflict) {
                throw new InvalidArgumentException('Room already has a defense schedule during the requested time interval.');
            }

            // Panelist conflict
            $panelConflict = DefensePanelAssignment::whereIn('user_id', $assignedUserIds)
                ->whereNull('ended_at')
                ->whereHas('defense', function ($q) {
                    $q->whereIn('status', ['scheduled', 'in_progress', 'rescheduled']);
                })
                ->whereHas('defense.currentSchedule', function ($q) use ($startsAt, $endsAt) {
                    $q->where('status', 'current')
                        ->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);
                })
                ->exists();

            if ($panelConflict) {
                throw new InvalidArgumentException('One or more panel members have an active schedule conflict during the requested time interval.');
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
            $positionedAssignments = [];
            if ($chairpersonUserId !== null) {
                $positionedAssignments['chairperson'] = $chairpersonUserId;
            }
            foreach ($panelUserIds as $index => $userId) {
                $positionedAssignments['member_'.($index + 1)] = $userId;
            }

            foreach ($positionedAssignments as $position => $uId) {
                DefensePanelAssignment::create([
                    'defense_id' => $defense->id,
                    'user_id' => $uId,
                    'panel_position' => $position,
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
                    'chairperson_user_id' => $chairpersonUserId,
                    'panel_user_ids' => $panelUserIds,
                ],
            ]);

            $label = str($defenseType)->headline()->toString();
            $students = $lockedGroup->members()->with('student')->get()->pluck('student')->filter();
            $this->notifications->sendToMany(
                recipients: $students,
                eventKey: 'defense.scheduled',
                title: "{$label} scheduled",
                message: "Your {$label} is scheduled for {$startsAt->format('M j, Y g:i A')} in {$room->name}.",
                category: 'defense',
                routeName: 'student.dashboard',
                routeParameters: ['tab' => 'defense'],
                sourceType: DefenseSchedule::class,
                sourceId: $schedule->getKey(),
                actor: $actor,
                contextLabel: $lockedGroup->name,
                actingAs: 'Student Researcher',
            );

            $adviser = $lockedGroup->adviser_id !== null ? User::query()->find($lockedGroup->adviser_id) : null;
            if ($adviser !== null) {
                $this->notifications->send(
                    recipient: $adviser,
                    eventKey: 'defense.scheduled',
                    title: "{$label} scheduled",
                    message: "{$lockedGroup->name} is scheduled for {$startsAt->format('M j, Y g:i A')}.",
                    category: 'defense',
                    routeName: 'adviser.dashboard',
                    routeParameters: ['tab' => 'evaluations'],
                    sourceType: DefenseSchedule::class,
                    sourceId: $schedule->getKey(),
                    actor: $actor,
                    contextLabel: $lockedGroup->name,
                    actingAs: 'Thesis Adviser',
                );
            }

            $this->notifications->sendToMany(
                recipients: $panelUsers,
                eventKey: 'defense.panel-assigned',
                title: "Assigned to {$label}",
                message: "You were assigned to {$lockedGroup->name}'s {$label} on {$startsAt->format('M j, Y g:i A')}.",
                category: 'defense',
                routeName: 'panelist.dashboard',
                routeParameters: ['tab' => 'schedule'],
                sourceType: Defense::class,
                sourceId: $defense->getKey(),
                actor: $actor,
                contextLabel: $lockedGroup->name,
                actingAs: 'Panel Member',
            );

            return $defense->fresh(['currentSchedule.room', 'activePanelAssignments.user']);
        });
    }
}

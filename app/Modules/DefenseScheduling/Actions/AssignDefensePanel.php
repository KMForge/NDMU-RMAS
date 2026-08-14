<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignDefensePanel
{
    public function handle(
        User $actor,
        Defense $defense,
        array $proposedPanelUserIds
    ): Defense {
        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense panel assignments.');
        }

        $group = $defense->group;
        if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
        }

        if (array_values(array_unique($proposedPanelUserIds)) !== array_values($proposedPanelUserIds)) {
            throw new InvalidArgumentException('Duplicate panel user IDs in request.');
        }

        return DB::transaction(function () use ($actor, $defense, $proposedPanelUserIds) {
            // 1. Lock Group & Defense
            $lockedGroup = ResearchClassGroup::where('id', $defense->research_class_group_id)->lockForUpdate()->firstOrFail();
            $lockedDefense = Defense::where('id', $defense->id)->lockForUpdate()->firstOrFail();

            // 2. Existing active panel members
            $existingActiveAssignments = DefensePanelAssignment::where('defense_id', $lockedDefense->id)
                ->whereNull('ended_at')
                ->get();
            $existingUserIds = $existingActiveAssignments->pluck('user_id')->toArray();

            // 3. Union of all panel user IDs sorted ASC
            $allUserIds = array_values(array_unique(array_merge($existingUserIds, $proposedPanelUserIds)));
            sort($allUserIds);

            if (! empty($allUserIds)) {
                $lockedUsers = User::whereIn('id', $allUserIds)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();
            } else {
                $lockedUsers = collect();
            }

            // 4. Verify candidate eligibility for proposed panel members
            $proposedUsers = $lockedUsers->whereIn('id', $proposedPanelUserIds);
            if ($proposedUsers->count() !== count($proposedPanelUserIds)) {
                throw new InvalidArgumentException('One or more proposed panel user IDs were not found.');
            }

            foreach ($proposedUsers as $u) {
                if ($u->user_type !== UserType::Faculty || $u->status !== AccountStatus::Active || $u->approved_at === null || $u->email_verified_at === null || ! $u->can('evaluations.create')) {
                    throw new InvalidArgumentException("User {$u->id} is not an eligible Defense Panel candidate.");
                }
            }

            // 5. Overlap check for proposed panel members during current schedule (if schedule exists)
            $currentSchedule = $lockedDefense->currentSchedule;
            if ($currentSchedule && $currentSchedule->status === 'current') {
                $startsAt = $currentSchedule->starts_at;
                $endsAt = $currentSchedule->ends_at;

                $panelConflict = DefensePanelAssignment::whereIn('user_id', $proposedPanelUserIds)
                    ->whereNull('ended_at')
                    ->where('defense_id', '!=', $lockedDefense->id)
                    ->whereHas('defense.currentSchedule', function ($q) use ($currentSchedule, $startsAt, $endsAt) {
                        $q->where('id', '!=', $currentSchedule->id)
                            ->where('status', 'current')
                            ->where('starts_at', '<', $endsAt)
                            ->where('ends_at', '>', $startsAt);
                    })
                    ->exists();

                if ($panelConflict) {
                    throw new InvalidArgumentException('One or more proposed panel members have a schedule conflict on another defense.');
                }
            }

            // 6. Diff calculation
            $toAdd = array_values(array_diff($proposedPanelUserIds, $existingUserIds));
            $toEnd = array_values(array_diff($existingUserIds, $proposedPanelUserIds));

            if (empty($toAdd) && empty($toEnd)) {
                return $lockedDefense->fresh(['activePanelAssignments.user']);
            }

            // 7. End removed assignments
            if (! empty($toEnd)) {
                DefensePanelAssignment::where('defense_id', $lockedDefense->id)
                    ->whereIn('user_id', $toEnd)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now()]);
            }

            // 8. Add missing assignments
            foreach ($toAdd as $addId) {
                DefensePanelAssignment::create([
                    'defense_id' => $lockedDefense->id,
                    'user_id' => $addId,
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                ]);
            }

            // 9. Audit log
            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'defense.panel_assigned',
                'auditable_type' => Defense::class,
                'auditable_id' => $lockedDefense->id,
                'description' => "Updated panel assignments for defense #{$lockedDefense->id}.",
                'subject_snapshot' => [
                    'proposed_panel_user_ids' => $proposedPanelUserIds,
                    'added_user_ids' => $toAdd,
                    'ended_user_ids' => $toEnd,
                ],
            ]);

            return $lockedDefense->fresh(['activePanelAssignments.user']);
        });
    }
}

<?php

namespace App\Modules\DefenseScheduling\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseSchedule;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelDefense
{
    public function handle(
        User $actor,
        Defense $defense,
        int $expectedCurrentScheduleId,
        string $reason
    ): Defense {
        if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized to manage defense schedules.');
        }

        $group = $defense->group;
        if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
            throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A valid reason is required for cancellation.');
        }

        return DB::transaction(function () use ($actor, $defense, $expectedCurrentScheduleId, $reason) {
            $lockedGroup = ResearchClassGroup::where('id', $defense->research_class_group_id)->lockForUpdate()->firstOrFail();
            $lockedDefense = Defense::where('id', $defense->id)->lockForUpdate()->firstOrFail();

            if ($actor->user_type !== UserType::Faculty || $actor->status !== AccountStatus::Active || ! $actor->can('defenses.manage')) {
                throw new AuthorizationException('Unauthorized to manage defense schedules.');
            }

            if (! $lockedGroup->researchClass || (int) $lockedGroup->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
            }

            if (in_array($lockedDefense->status, ['cancelled', 'completed'], true) || $lockedDefense->current_schedule_id === null) {
                throw new InvalidArgumentException("Cannot cancel a {$lockedDefense->status} defense.");
            }

            $activeRound = DefenseEvaluationRound::query()
                ->where('defense_id', $lockedDefense->id)
                ->whereIn('status', ['open', 'in_progress', 'complete', 'finalized', 'released'])
                ->first();

            if ($activeRound) {
                throw new InvalidArgumentException('Cannot cancel defense: An evaluation round exists for this defense.');
            }

            if ((int) $lockedDefense->current_schedule_id !== (int) $expectedCurrentScheduleId) {
                throw new InvalidArgumentException('Stale schedule reference: Current defense schedule has changed.');
            }

            $schedule = DefenseSchedule::where('id', $expectedCurrentScheduleId)->lockForUpdate()->firstOrFail();
            if ((int) $schedule->defense_id !== (int) $lockedDefense->id) {
                throw new InvalidArgumentException('Schedule does not belong to specified defense.');
            }

            $schedule->status = 'cancelled';
            $schedule->reason = $reason;
            $schedule->save();

            $lockedDefense->current_schedule_id = null;
            $lockedDefense->status = 'cancelled';
            $lockedDefense->save();

            DefensePanelAssignment::where('defense_id', $lockedDefense->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'defense.cancelled',
                'auditable_type' => Defense::class,
                'auditable_id' => $lockedDefense->id,
                'description' => "Cancelled defense #{$lockedDefense->id}.",
                'subject_snapshot' => [
                    'cancelled_schedule_id' => $schedule->id,
                    'reason' => $reason,
                ],
            ]);

            return $lockedDefense->fresh();
        });
    }
}

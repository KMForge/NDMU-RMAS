<?php

namespace App\Modules\TitlePresentations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\DefensePanelAssignment;
use App\Models\ResearchClassGroup;
use App\Models\TitlePresentation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignTitlePresentationPanel
{
    /** @param array{chairperson: int, member_1: int, member_2: int} $assignments */
    public function handle(User $actor, TitlePresentation $presentation, array $assignments, ?string $changeReason = null): TitlePresentation
    {
        if (count(array_unique(array_values($assignments))) !== 3) {
            throw new InvalidArgumentException('The Chairperson and two Panel Members must be three distinct Faculty users.');
        }

        return DB::transaction(function () use ($actor, $presentation, $assignments, $changeReason): TitlePresentation {
            $locked = TitlePresentation::query()->with('defense.currentSchedule')->lockForUpdate()->findOrFail($presentation->id);
            $group = ResearchClassGroup::query()->with('researchClass')->lockForUpdate()->findOrFail($locked->defense->research_class_group_id);
            $actor->refresh();

            $eligible = $actor->user_type === UserType::Faculty
                && $actor->status === AccountStatus::Active
                && $actor->approved_at !== null
                && $actor->email_verified_at !== null
                && $actor->can('defenses.manage')
                && (int) $group->researchClass?->facilitator_id === (int) $actor->id;

            if (! $eligible) {
                throw new AuthorizationException('Only the owning Research Facilitator may assign this Title Presentation panel.');
            }
            if ($locked->defense->current_schedule_id === null || ! in_array($locked->status, ['scheduled', 'panel_assigned'], true)) {
                throw new InvalidArgumentException('A current Title Presentation schedule is required before panel assignment.');
            }

            $userIds = array_values($assignments);
            sort($userIds);
            $faculty = User::query()->whereIn('id', $userIds)->orderBy('id')->lockForUpdate()->get();
            if ($faculty->count() !== 3) {
                throw new InvalidArgumentException('One or more selected Panel users do not exist.');
            }
            foreach ($faculty as $candidate) {
                if ($candidate->user_type !== UserType::Faculty || $candidate->status !== AccountStatus::Active || $candidate->approved_at === null || $candidate->email_verified_at === null || ! $candidate->can('evaluations.create')) {
                    throw new InvalidArgumentException("User {$candidate->id} is not an eligible Title Presentation Panel candidate.");
                }
            }

            $schedule = $locked->defense->currentSchedule;
            $conflict = DefensePanelAssignment::query()
                ->whereIn('user_id', $userIds)->whereNull('ended_at')->where('defense_id', '!=', $locked->defense_id)
                ->whereHas('defense.currentSchedule', fn ($query) => $query->where('status', 'current')->where('starts_at', '<', $schedule->ends_at)->where('ends_at', '>', $schedule->starts_at))
                ->exists();
            if ($conflict) {
                throw new InvalidArgumentException('One or more selected Panel users have a schedule conflict.');
            }

            $existing = DefensePanelAssignment::query()->where('defense_id', $locked->defense_id)->whereNull('ended_at')->get();
            $existingAssignments = $existing
                ->mapWithKeys(fn (DefensePanelAssignment $assignment): array => [
                    $assignment->panel_position => (int) $assignment->user_id,
                ])
                ->all();
            $requestedAssignments = collect($assignments)
                ->map(fn ($userId): int => (int) $userId)
                ->all();

            // ScheduleDefense may already have populated the saved group committee.
            // Re-applying that exact fetched roster is idempotent, not a historical
            // reassignment, so it must not require a correction reason or duplicate rows.
            $isSameRoster = $existing->count() === count($requestedAssignments)
                && collect($requestedAssignments)->every(
                    fn (int $userId, string $position): bool => ($existingAssignments[$position] ?? null) === $userId,
                );

            if ($isSameRoster) {
                if ($locked->status !== 'panel_assigned') {
                    $locked->update(['status' => 'panel_assigned']);
                }

                return $locked->fresh(['defense.currentSchedule.room', 'defense.activePanelAssignments.user']);
            }

            $changed = $existing->isNotEmpty();
            if ($changed && trim((string) $changeReason) === '') {
                throw new InvalidArgumentException('A reason is required when changing a historical Title Presentation panel assignment.');
            }
            $existing->each(fn (DefensePanelAssignment $assignment) => $assignment->update(['ended_at' => now(), 'change_reason' => trim((string) $changeReason)]));

            foreach ($assignments as $position => $userId) {
                DefensePanelAssignment::query()->create([
                    'defense_id' => $locked->defense_id, 'user_id' => $userId, 'panel_position' => $position,
                    'assigned_by' => $actor->id, 'assigned_at' => now(),
                ]);
            }
            $locked->update(['status' => 'panel_assigned']);

            AuditLog::query()->create([
                'user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_email' => $actor->email,
                'event' => $changed ? 'TITLE_PANEL_CHANGED' : 'TITLE_PANEL_ASSIGNED', 'auditable_type' => TitlePresentation::class, 'auditable_id' => $locked->id,
                'description' => $changed ? 'Changed the Title Presentation panel roster.' : 'Assigned the Title Presentation panel roster.',
                'subject_snapshot' => ['academic_actor_type' => 'research_facilitator', 'assignments' => $assignments, 'change_reason' => $changeReason],
            ]);

            return $locked->fresh(['defense.currentSchedule.room', 'defense.activePanelAssignments.user']);
        }, 3);
    }
}

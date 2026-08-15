<?php

namespace App\Modules\Evaluations\Actions;

use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationRoundPanelist;
use App\Models\DefenseEvaluationRoundStudent;
use App\Models\DefenseSchedule;
use App\Models\User;
use App\Modules\Evaluations\Services\EvaluationAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OpenDefenseEvaluationRound
{
    public function __construct(
        private readonly EvaluationAuthorization $auth = new EvaluationAuthorization
    ) {}

    public function handle(User $actor, Defense $defense, ?int $designatedSignerUserId = null): DefenseEvaluationRound
    {
        $this->auth->assertFacultyActor($actor);

        return DB::transaction(function () use ($actor, $defense, $designatedSignerUserId) {
            /** @var Defense $lockedDefense */
            $lockedDefense = Defense::query()->lockForUpdate()->with(['group.researchClass', 'activePanelAssignments.user'])->findOrFail($defense->id);

            $this->auth->assertFacilitatorOwnsDefense($actor, $lockedDefense, 'defenses.manage');

            if ($lockedDefense->status !== 'scheduled' || $lockedDefense->current_schedule_id === null) {
                throw new InvalidArgumentException('Cannot open an evaluation round for a defense that is not scheduled.');
            }

            /** @var DefenseSchedule $schedule */
            $schedule = DefenseSchedule::query()->lockForUpdate()->findOrFail($lockedDefense->current_schedule_id);
            if ($schedule->status !== 'current') {
                throw new InvalidArgumentException('Target defense schedule is not current.');
            }

            // Check if active round already exists
            $existingRound = DefenseEvaluationRound::query()
                ->where('defense_schedule_id', $schedule->id)
                ->whereIn('status', ['open', 'in_progress', 'complete', 'finalized', 'released'])
                ->first();

            if ($existingRound) {
                throw new InvalidArgumentException('An evaluation round is already open or completed for this defense schedule.');
            }

            // Verify exactly 3 active panel assignments
            $activeAssignments = $lockedDefense->activePanelAssignments;
            if ($activeAssignments->count() !== 3) {
                throw new InvalidArgumentException("Defense evaluation round requires exactly 3 active panel assignments (found {$activeAssignments->count()}).");
            }

            // Determine summary signer
            $signerUserId = $designatedSignerUserId ?? $activeAssignments->first()?->user_id;

            if (! $activeAssignments->pluck('user_id')->contains($signerUserId)) {
                throw new InvalidArgumentException("Designated summary signer user #{$signerUserId} is not an assigned panelist for this defense.");
            }

            $round = DefenseEvaluationRound::query()->create([
                'defense_id' => $lockedDefense->id,
                'defense_schedule_id' => $schedule->id,
                'research_class_group_id' => $lockedDefense->research_class_group_id,
                'status' => 'open',
                'summary_signer_user_id' => $signerUserId,
                'opened_by' => $actor->id,
                'opened_at' => now(),
            ]);

            // Freeze panelist roster
            foreach ($activeAssignments as $index => $assignment) {
                DefenseEvaluationRoundPanelist::query()->create([
                    'defense_evaluation_round_id' => $round->id,
                    'defense_panel_assignment_id' => $assignment->id,
                    'panelist_user_id' => $assignment->user_id,
                    'position' => $index + 1,
                ]);
            }

            // Freeze student roster
            $members = $lockedDefense->group?->members ?? collect();
            if ($members->isEmpty()) {
                throw new InvalidArgumentException('Cannot open evaluation round: Research group has no enrolled student members.');
            }

            foreach ($members as $member) {
                DefenseEvaluationRoundStudent::query()->create([
                    'defense_evaluation_round_id' => $round->id,
                    'student_id' => $member->student_id,
                    'student_name_snapshot' => $member->student?->name ?? "Student #{$member->student_id}",
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'evaluation_round.opened',
                'auditable_type' => DefenseEvaluationRound::class,
                'auditable_id' => $round->id,
                'description' => "Opened defense evaluation round #{$round->id} for defense #{$lockedDefense->id}.",
            ]);

            return $round->load(['roundPanelists', 'roundStudents']);
        });
    }
}

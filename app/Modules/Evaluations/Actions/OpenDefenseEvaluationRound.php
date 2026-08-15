<?php

namespace App\Modules\Evaluations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationRoundPanelist;
use App\Models\DefenseEvaluationRoundStudent;
use App\Models\DefenseSchedule;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OpenDefenseEvaluationRound
{
    public function handle(User $actor, Defense $defense, ?int $designatedSignerUserId = null): DefenseEvaluationRound
    {
        if ($actor->user_type !== UserType::Faculty
            || $actor->status !== AccountStatus::Active
            || $actor->approved_at === null
            || $actor->email_verified_at === null
            || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized: You lack faculty credentials or permission to manage defenses.');
        }

        return DB::transaction(function () use ($actor, $defense, $designatedSignerUserId) {
            /** @var Defense $lockedDefense */
            $lockedDefense = Defense::query()->lockForUpdate()->with(['group.researchClass', 'activePanelAssignments.user'])->findOrFail($defense->id);

            $group = $lockedDefense->group;
            if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
            }

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
                throw new InvalidArgumentException('Evaluation round requires exactly 3 active panel assignments.');
            }

            // Verify all 3 panelists eligibility
            foreach ($activeAssignments as $assignment) {
                $p = $assignment->user;
                if (! $p || $p->user_type !== UserType::Faculty
                    || $p->status !== AccountStatus::Active
                    || $p->approved_at === null
                    || $p->email_verified_at === null
                    || ! $p->can('evaluations.create')
                    || ! $p->can('forms.res-036.evaluate')) {
                    throw new InvalidArgumentException("Panelist #{$assignment->user_id} is not eligible to participate in defense evaluation.");
                }
            }

            // Verify designated signer if provided
            $signerUser = null;
            if ($designatedSignerUserId !== null) {
                $signerAssignment = $activeAssignments->firstWhere('user_id', $designatedSignerUserId);
                if (! $signerAssignment) {
                    throw new InvalidArgumentException('Designated summary signer must be one of the assigned evaluation panelists.');
                }
                $signerUser = $signerAssignment->user;
                if (! $signerUser->can('forms.res-037.sign')) {
                    throw new InvalidArgumentException("Designated signer #{$designatedSignerUserId} lacks forms.res-037.sign permission.");
                }
            }

            // Freeze student roster
            $members = $group->members()->with('student')->get();
            if ($members->isEmpty()) {
                throw new InvalidArgumentException('Cannot open evaluation round for a research group with no members.');
            }

            $round = DefenseEvaluationRound::query()->create([
                'defense_id' => $lockedDefense->id,
                'defense_schedule_id' => $schedule->id,
                'research_class_group_id' => $group->id,
                'status' => 'open',
                'summary_signer_user_id' => $signerUser?->id,
                'opened_by' => $actor->id,
                'opened_at' => now(),
            ]);

            // Freeze panelist roster
            $position = 1;
            foreach ($activeAssignments as $assignment) {
                DefenseEvaluationRoundPanelist::query()->create([
                    'defense_evaluation_round_id' => $round->id,
                    'defense_panel_assignment_id' => $assignment->id,
                    'panelist_user_id' => $assignment->user_id,
                    'position' => $position++,
                ]);
            }

            // Freeze student roster
            foreach ($members as $member) {
                DefenseEvaluationRoundStudent::query()->create([
                    'defense_evaluation_round_id' => $round->id,
                    'student_id' => $member->student_id,
                    'student_name_snapshot' => $member->student?->name ?? 'Student #'.$member->student_id,
                    'group_member_id' => $member->id,
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'evaluation_round.opened',
                'auditable_type' => DefenseEvaluationRound::class,
                'auditable_id' => $round->id,
                'description' => "Opened evaluation round #{$round->id} for defense #{$lockedDefense->id}.",
            ]);

            return $round->load(['roundPanelists.panelist', 'roundStudents.student', 'summarySigner']);
        });
    }
}

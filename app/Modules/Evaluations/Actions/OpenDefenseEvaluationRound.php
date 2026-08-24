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
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OpenDefenseEvaluationRound
{
    public function __construct(
        private readonly EvaluationAuthorization $auth = new EvaluationAuthorization,
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    public function handle(User $actor, Defense $defense, ?int $designatedSignerUserId = null): DefenseEvaluationRound
    {
        $this->auth->assertFacultyActor($actor);

        return DB::transaction(function () use ($actor, $defense, $designatedSignerUserId) {
            /** @var Defense $lockedDefense */
            $lockedDefense = Defense::query()->lockForUpdate()->with(['group.researchClass', 'activePanelAssignments'])->findOrFail($defense->id);

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

            // Query fresh User instances inside transaction to ensure fresh eligibility state
            $panelistUserIds = $activeAssignments->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            $freshPanelists = User::query()->whereIn('id', $panelistUserIds)->get()->keyBy('id');

            // Pre-freeze candidate eligibility check: EVERY assigned panelist must pass
            foreach ($activeAssignments as $assignment) {
                $panelistUser = $freshPanelists->get((int) $assignment->user_id);
                if (! $panelistUser) {
                    throw new InvalidArgumentException("Assigned panelist user #{$assignment->user_id} not found.");
                }

                $this->auth->assertEligiblePanelCandidate($panelistUser);
            }

            // Determine and validate summary signer
            $signerUserId = null;

            if ($designatedSignerUserId !== null) {
                if (! in_array((int) $designatedSignerUserId, $panelistUserIds, true)) {
                    throw new InvalidArgumentException("Designated summary signer user #{$designatedSignerUserId} is not an assigned panelist for this defense.");
                }

                $designatedUser = $freshPanelists->get((int) $designatedSignerUserId);
                $this->auth->assertSummarySignerCandidate($designatedUser);
                $signerUserId = (int) $designatedSignerUserId;
            } else {
                // Default signer selection: pick first eligible candidate with forms.res-037.sign
                foreach ($activeAssignments as $assignment) {
                    $candidateUser = $freshPanelists->get((int) $assignment->user_id);
                    if ($candidateUser && $candidateUser->can('forms.res-037.sign')) {
                        $signerUserId = (int) $candidateUser->id;
                        break;
                    }
                }

                if ($signerUserId === null) {
                    throw new InvalidArgumentException('No assigned panelist holds forms.res-037.sign permission to sign RES-037 summary.');
                }
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

            $this->notifications->sendToMany(
                recipients: $freshPanelists->values(),
                eventKey: 'evaluation.round.opened',
                title: 'Defense evaluation ready',
                message: "The evaluation round for {$lockedDefense->group?->name} is ready for your evaluation.",
                category: 'evaluation',
                routeName: 'panelist.dashboard',
                routeParameters: ['tab' => in_array($lockedDefense->defense_type, ['pre_final_defense', 'final_defense'], true) ? 'final-eval' : 'proposal-eval'],
                sourceType: DefenseEvaluationRound::class,
                sourceId: $round->getKey(),
                actor: $actor,
                contextLabel: $lockedDefense->group?->name,
                actingAs: 'Panel Member',
                occurrence: 'open',
            );

            return $round->load(['roundPanelists', 'roundStudents']);
        });
    }
}

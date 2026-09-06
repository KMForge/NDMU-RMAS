<?php

namespace App\Modules\Evaluations\Actions;

use App\Models\AuditLog;
use App\Models\DefenseEvaluationRound;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormSignature;
use App\Models\User;
use App\Modules\Evaluations\Services\EvaluationAuthorization;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReleaseDefenseEvaluationResults
{
    public function __construct(
        private readonly EvaluationAuthorization $auth = new EvaluationAuthorization,
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    public function handle(User $actor, DefenseEvaluationRound $round): DefenseEvaluationRound
    {
        $this->auth->assertFacultyActor($actor);

        return DB::transaction(function () use ($actor, $round) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['defense.group.researchClass', 'summary'])->findOrFail($round->id);

            $this->auth->assertFacilitatorOwnsRound($actor, $lockedRound, 'evaluations.release');

            if ($lockedRound->status === 'released') {
                return $lockedRound; // Idempotent
            }

            if ($lockedRound->status !== 'finalized') {
                throw new InvalidArgumentException('Cannot release evaluation results: Round must be finalized with a signed RES-037.');
            }

            // In-depth verification of valid Phase 20 digital signature relation
            $inst037 = OfficialFormInstance::query()
                ->where('source_type', DefenseEvaluationRound::class)
                ->where('source_id', $lockedRound->id)
                ->first();

            if (! $inst037 || ! $inst037->current_version_id) {
                throw new InvalidArgumentException('Cannot release evaluation results: RES-037 form instance is missing.');
            }

            $hasSignature = OfficialFormSignature::query()
                ->where('official_form_version_id', $inst037->current_version_id)
                ->where('signer_user_id', $lockedRound->summary_signer_user_id)
                ->where('academic_action', 'sign')
                ->exists();

            if (! $hasSignature) {
                throw new InvalidArgumentException('Cannot release evaluation results: RES-037 missing verified digital signature.');
            }

            $now = now();

            if ($lockedRound->summary) {
                $lockedRound->summary->update([
                    'released_at' => $now,
                ]);
            }

            $lockedRound->update([
                'status' => 'released',
                'released_at' => $now,
                'released_by' => $actor->id,
            ]);

            if ($lockedRound->defense) {
                $lockedRound->defense->update(['status' => 'completed']);
            }
            if ($lockedRound->defenseSchedule) {
                $lockedRound->defenseSchedule->update(['status' => 'completed']);
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'evaluation_results.released',
                'auditable_type' => DefenseEvaluationRound::class,
                'auditable_id' => $lockedRound->id,
                'description' => "Released evaluation results for round #{$lockedRound->id}.",
            ]);

            $group = $lockedRound->defense?->group;
            $students = $group?->members()->with('student')->get()->pluck('student')->filter() ?? collect();

            $this->notifications->sendToMany(
                recipients: $students,
                eventKey: 'evaluation.results.released',
                title: 'Defense evaluation results released',
                message: 'Your defense evaluation results are now available.',
                category: 'evaluation',
                routeName: 'student.dashboard',
                routeParameters: ['tab' => 'evaluations'],
                sourceType: DefenseEvaluationRound::class,
                sourceId: $lockedRound->getKey(),
                actor: $actor,
                contextLabel: $group?->name,
                actingAs: 'Student Researcher',
                occurrence: 'released',
            );

            return $lockedRound->fresh(['summary']);
        });
    }
}

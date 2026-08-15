<?php

namespace App\Modules\Evaluations\Actions;

use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\User;
use App\Modules\Evaluations\Services\EvaluationAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteDefenseAfterEvaluation
{
    public function __construct(
        private readonly EvaluationAuthorization $auth = new EvaluationAuthorization
    ) {}

    public function handle(User $actor, Defense $defense): Defense
    {
        $this->auth->assertFacultyActor($actor);

        return DB::transaction(function () use ($actor, $defense) {
            /** @var Defense $lockedDefense */
            $lockedDefense = Defense::query()->lockForUpdate()->with(['group.researchClass', 'evaluationRounds.summary'])->findOrFail($defense->id);

            $this->auth->assertFacilitatorOwnsDefense($actor, $lockedDefense, 'defenses.manage');

            if ($lockedDefense->status === 'completed') {
                return $lockedDefense; // Idempotent
            }

            if ($lockedDefense->status !== 'scheduled') {
                throw new InvalidArgumentException('Cannot complete a defense that is not scheduled.');
            }

            /** @var DefenseEvaluationRound|null $releasedRound */
            $releasedRound = $lockedDefense->evaluationRounds->firstWhere('status', 'released');
            if (! $releasedRound) {
                throw new InvalidArgumentException('Cannot complete defense: Evaluation results must be released first.');
            }

            $now = now();

            $lockedDefense->update([
                'status' => 'completed',
                'completed_at' => $now,
                'completed_by' => $actor->id,
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'defense.completed',
                'auditable_type' => Defense::class,
                'auditable_id' => $lockedDefense->id,
                'description' => "Marked defense #{$lockedDefense->id} as completed.",
            ]);

            return $lockedDefense->fresh(['evaluationRounds.summary']);
        });
    }
}

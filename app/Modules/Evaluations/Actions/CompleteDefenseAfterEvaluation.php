<?php

namespace App\Modules\Evaluations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompleteDefenseAfterEvaluation
{
    public function handle(User $actor, Defense $defense): Defense
    {
        if ($actor->user_type !== UserType::Faculty
            || $actor->status !== AccountStatus::Active
            || $actor->approved_at === null
            || $actor->email_verified_at === null
            || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized: You lack faculty credentials or permission to manage defenses.');
        }

        return DB::transaction(function () use ($actor, $defense) {
            /** @var Defense $lockedDefense */
            $lockedDefense = Defense::query()->lockForUpdate()->with(['group.researchClass', 'evaluationRounds.summary'])->findOrFail($defense->id);

            $group = $lockedDefense->group;
            if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
            }

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

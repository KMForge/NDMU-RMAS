<?php

namespace App\Modules\Evaluations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\DefenseEvaluationRound;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReleaseDefenseEvaluationResults
{
    public function handle(User $actor, DefenseEvaluationRound $round): DefenseEvaluationRound
    {
        if ($actor->user_type !== UserType::Faculty
            || $actor->status !== AccountStatus::Active
            || $actor->approved_at === null
            || $actor->email_verified_at === null
            || ! $actor->can('evaluations.release')) {
            throw new AuthorizationException('Unauthorized: You lack faculty credentials or permission to release evaluation results.');
        }

        return DB::transaction(function () use ($actor, $round) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['defense.group.researchClass', 'summary'])->findOrFail($round->id);

            $group = $lockedRound->defense->group;
            if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
            }

            if ($lockedRound->status === 'released') {
                return $lockedRound; // Idempotent
            }

            if ($lockedRound->status !== 'finalized') {
                throw new InvalidArgumentException('Cannot release evaluation results: Round must be finalized with a signed RES-037.');
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

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'evaluation_results.released',
                'auditable_type' => DefenseEvaluationRound::class,
                'auditable_id' => $lockedRound->id,
                'description' => "Released evaluation results for round #{$lockedRound->id}.",
            ]);

            return $lockedRound->fresh(['summary']);
        });
    }
}

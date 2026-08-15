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

class DesignateEvaluationSummarySigner
{
    public function handle(User $actor, DefenseEvaluationRound $round, int $signerUserId): DefenseEvaluationRound
    {
        if ($actor->user_type !== UserType::Faculty
            || $actor->status !== AccountStatus::Active
            || $actor->approved_at === null
            || $actor->email_verified_at === null
            || ! $actor->can('defenses.manage')) {
            throw new AuthorizationException('Unauthorized: You lack faculty credentials or permission to manage defenses.');
        }

        return DB::transaction(function () use ($actor, $round, $signerUserId) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['defense.group.researchClass', 'roundPanelists.panelist'])->findOrFail($round->id);

            $group = $lockedRound->defense->group;
            if (! $group || ! $group->researchClass || (int) $group->researchClass->facilitator_id !== (int) $actor->id) {
                throw new AuthorizationException('Unauthorized: You do not own the research class for this defense.');
            }

            if (in_array($lockedRound->status, ['finalized', 'released'], true)) {
                throw new InvalidArgumentException('Cannot change summary signer on a finalized or released evaluation round.');
            }

            $roundPanelist = $lockedRound->roundPanelists->firstWhere('panelist_user_id', $signerUserId);
            if (! $roundPanelist) {
                throw new InvalidArgumentException('Designated summary signer must be one of the frozen evaluation panelists.');
            }

            $signer = $roundPanelist->panelist;
            if (! $signer || $signer->user_type !== UserType::Faculty
                || $signer->status !== AccountStatus::Active
                || $signer->approved_at === null
                || $signer->email_verified_at === null
                || ! $signer->can('forms.res-037.sign')) {
                throw new InvalidArgumentException("Designated user #{$signerUserId} is not eligible or lacks forms.res-037.sign permission.");
            }

            $lockedRound->update([
                'summary_signer_user_id' => $signerUserId,
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'evaluation_round.signer_designated',
                'auditable_type' => DefenseEvaluationRound::class,
                'auditable_id' => $lockedRound->id,
                'description' => "Designated user #{$signerUserId} as summary signer for evaluation round #{$lockedRound->id}.",
            ]);

            return $lockedRound->fresh(['summarySigner']);
        });
    }
}

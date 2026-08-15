<?php

namespace App\Modules\Evaluations\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\DefenseEvaluationRound;
use App\Models\User;
use App\Modules\Evaluations\Services\EvaluationAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DesignateEvaluationSummarySigner
{
    public function __construct(
        private readonly EvaluationAuthorization $auth = new EvaluationAuthorization
    ) {}

    public function handle(User $actor, DefenseEvaluationRound $round, int $signerUserId): DefenseEvaluationRound
    {
        $this->auth->assertFacultyActor($actor);

        return DB::transaction(function () use ($actor, $round, $signerUserId) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with(['defense.group.researchClass', 'roundPanelists.panelist'])->findOrFail($round->id);

            $this->auth->assertFacilitatorOwnsRound($actor, $lockedRound, 'defenses.manage');

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

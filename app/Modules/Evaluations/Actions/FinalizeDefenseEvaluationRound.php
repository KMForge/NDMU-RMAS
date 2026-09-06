<?php

namespace App\Modules\Evaluations\Actions;

use App\Models\AuditLog;
use App\Models\DefenseEvaluationRound;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormSignature;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinalizeDefenseEvaluationRound
{
    public function handle(DefenseEvaluationRound $round): DefenseEvaluationRound
    {
        return DB::transaction(function () use ($round) {
            /** @var DefenseEvaluationRound $lockedRound */
            $lockedRound = DefenseEvaluationRound::query()->lockForUpdate()->with('summary')->findOrFail($round->id);

            if ($lockedRound->status === 'finalized' || $lockedRound->status === 'released') {
                return $lockedRound; // Idempotent
            }

            if ($lockedRound->status !== 'complete') {
                throw new InvalidArgumentException('Evaluation round must be complete before finalization.');
            }

            if ($lockedRound->summary_signer_user_id === null) {
                throw new InvalidArgumentException('Evaluation round has no designated summary signer.');
            }

            // Find RES-037 instance for this round
            $inst037 = OfficialFormInstance::query()
                ->where('source_type', DefenseEvaluationRound::class)
                ->where('source_id', $lockedRound->id)
                ->with(['currentVersion', 'definition'])
                ->first();

            if (! $inst037 || ! $inst037->current_version_id) {
                throw new InvalidArgumentException('RES-037 form instance not found for this evaluation round.');
            }

            // Verify valid Phase 20 signature by designated signer on exact current version
            $validSignature = OfficialFormSignature::query()
                ->where('official_form_version_id', $inst037->current_version_id)
                ->where('signer_user_id', $lockedRound->summary_signer_user_id)
                ->where('academic_action', 'sign')
                ->latest()
                ->first();

            if (! $validSignature) {
                throw new InvalidArgumentException('Designated summary signer has not digitally signed the RES-037 form version.');
            }

            $now = now();

            if ($lockedRound->summary) {
                $lockedRound->summary->update([
                    'status' => 'finalized',
                    'finalized_at' => $now,
                    'signed_at' => $validSignature->signed_at ?? $now,
                ]);
            }

            $lockedRound->update([
                'status' => 'finalized',
                'finalized_at' => $now,
            ]);

            if ($lockedRound->defense) {
                $lockedRound->defense->update(['status' => 'completed']);
            }
            if ($lockedRound->defenseSchedule) {
                $lockedRound->defenseSchedule->update(['status' => 'completed']);
            }

            AuditLog::query()->create([
                'user_id' => $lockedRound->summary_signer_user_id,
                'actor_name' => $validSignature->signer_name_snapshot ?? 'Signer',
                'actor_email' => $validSignature->signer_email_snapshot ?? 'signer@ndmu.edu.ph',
                'event' => 'evaluation_round.finalized',
                'auditable_type' => DefenseEvaluationRound::class,
                'auditable_id' => $lockedRound->id,
                'description' => "Finalized evaluation round #{$lockedRound->id} following Phase 20 digital signature of RES-037.",
            ]);

            return $lockedRound->fresh(['summary']);
        });
    }
}

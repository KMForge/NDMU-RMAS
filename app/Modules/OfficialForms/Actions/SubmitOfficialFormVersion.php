<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Validators\OfficialFormPayloadValidator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitOfficialFormVersion
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization,
        private readonly OfficialFormPayloadValidator $payloadValidator = new OfficialFormPayloadValidator
    ) {}

    /** @var list<string> */
    private const ALLOWED_SUBMISSION_STATUSES = ['submitted', 'in_progress', 'pending_action'];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        User $actor,
        OfficialFormInstance $instance,
        array $payload,
        string $nextStatus = 'submitted'
    ): OfficialFormVersion {
        if (! in_array($nextStatus, self::ALLOWED_SUBMISSION_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid submission status [{$nextStatus}].");
        }

        $formCode = strtoupper($instance->definition->code);
        $validatedPayload = $this->payloadValidator->validate($formCode, $payload);

        if ($formCode === 'RES-026') {
            $topics = array_values(array_filter(
                array_map(static fn (mixed $title): string => trim((string) $title), $validatedPayload['topics'] ?? []),
                static fn (string $title): bool => $title !== ''
            ));

            if (count($topics) !== 3) {
                throw new InvalidArgumentException('RES-026 requires exactly three non-blank proposed research titles.');
            }

            foreach ($topics as $title) {
                if (mb_strlen($title) > 500) {
                    throw new InvalidArgumentException('Each proposed research title must not exceed 500 characters.');
                }
            }

            $validatedPayload['topics'] = $topics;
        }

        return DB::transaction(function () use ($actor, $instance, $nextStatus, $validatedPayload) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            if (in_array($lockedInstance->status, ['approved', 'completed', 'cancelled', 'superseded'], true)) {
                throw new InvalidArgumentException("Form instance #{$lockedInstance->id} is finalized and cannot accept new versions.");
            }

            if (! $this->authorization->canSubmit($actor, $lockedInstance)) {
                throw new InvalidArgumentException("User #{$actor->id} is not authorized to submit versions on form instance #{$lockedInstance->id}.");
            }

            $currentVersion = $lockedInstance->currentVersion;
            $oldStatus = $lockedInstance->status;

            if (strtoupper($lockedInstance->definition->code) === 'RES-048') {
                $validatedPayload = $this->preparePeerEvaluationPayload(
                    $actor,
                    $currentVersion?->source_snapshot,
                    $validatedPayload,
                );
            }

            if ($currentVersion !== null && $currentVersion->payload === $validatedPayload) {
                $lockedInstance->update(['status' => $nextStatus]);

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => strtoupper($lockedInstance->definition->code) === 'RES-026' ? 'RES026_SUBMITTED' : 'official_form.submitted',
                    'auditable_type' => OfficialFormInstance::class,
                    'auditable_id' => $lockedInstance->id,
                    'description' => "Submitted unchanged official form version v{$currentVersion->version_number} (status: {$nextStatus}).",
                    'subject_snapshot' => [
                        'actor_function' => 'form_submitter',
                        'old_status' => $oldStatus,
                        'new_status' => $nextStatus,
                        'version_number' => $currentVersion->version_number,
                    ],
                ]);

                return $currentVersion;
            }

            $nextVersionNumber = ($lockedInstance->versions()->max('version_number') ?? 0) + 1;

            if ($currentVersion) {
                $lockedInstance->versions()->where('id', $currentVersion->id)->update(['is_current' => false]);
            }

            $newVersion = OfficialFormVersion::query()->create([
                'official_form_instance_id' => $lockedInstance->id,
                'version_number' => $nextVersionNumber,
                'payload' => $validatedPayload,
                'source_snapshot' => $currentVersion?->source_snapshot,
                'created_by' => $actor->id,
                'supersedes_version_id' => $currentVersion?->id,
                'is_current' => true,
            ]);

            $lockedInstance->update([
                'current_version_id' => $newVersion->id,
                'status' => $nextStatus,
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => strtoupper($lockedInstance->definition->code) === 'RES-026' ? 'RES026_SUBMITTED' : 'official_form.version_submitted',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => "Submitted official form version v{$nextVersionNumber} (status: {$nextStatus}).",
                'subject_snapshot' => [
                    'actor_function' => 'form_submitter',
                    'old_status' => $oldStatus,
                    'new_status' => $nextStatus,
                    'version_number' => $nextVersionNumber,
                ],
            ]);

            return $newVersion;
        });
    }

    /**
     * @param  array<string, mixed>|null  $sourceSnapshot
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function preparePeerEvaluationPayload(User $actor, ?array $sourceSnapshot, array $payload): array
    {
        if ((int) ($sourceSnapshot['evaluator_user_id'] ?? 0) !== (int) $actor->id) {
            throw new InvalidArgumentException('RES-048 evaluator identity does not match its frozen roster.');
        }

        $roster = $sourceSnapshot['roster'] ?? null;
        if (! is_array($roster) || count($roster) < 1 || count($roster) > 4) {
            throw new InvalidArgumentException('RES-048 has no valid frozen group roster.');
        }

        if (! in_array($payload['evaluation_phase'] ?? null, ['proposal', 'final'], true)) {
            throw new InvalidArgumentException('RES-048 requires a proposal or final evaluation phase.');
        }

        if (($payload['evaluation_date'] ?? '') === '') {
            throw new InvalidArgumentException('RES-048 requires an evaluation date.');
        }

        $rows = array_values($payload['ratings'] ?? []);
        if (count($rows) !== 10) {
            throw new InvalidArgumentException('RES-048 requires ratings for all ten criteria.');
        }

        $columnCount = count($roster);
        $normalizedRows = [];
        $totals = array_fill(0, $columnCount, 0);

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('RES-048 contains a malformed rating row.');
            }

            $values = array_values($row);
            if (count($values) !== $columnCount) {
                throw new InvalidArgumentException('RES-048 rating columns must exactly match the frozen group roster.');
            }

            $normalizedRow = [];
            foreach ($values as $column => $rating) {
                if (! is_numeric($rating) || (int) $rating < 1 || (int) $rating > 4 || (string) (int) $rating !== trim((string) $rating)) {
                    throw new InvalidArgumentException('RES-048 ratings must be whole numbers between 1 and 4.');
                }

                $normalizedRow[] = (int) $rating;
                $totals[$column] += (int) $rating;
            }

            $normalizedRows[] = $normalizedRow;
        }

        return [
            'evaluation_phase' => $payload['evaluation_phase'],
            'ratings' => $normalizedRows,
            'evaluation_date' => $payload['evaluation_date'],
            'totals' => $totals,
        ];
    }
}

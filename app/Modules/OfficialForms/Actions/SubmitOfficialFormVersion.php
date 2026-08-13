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

            if ($currentVersion !== null && $currentVersion->payload === $validatedPayload) {
                $lockedInstance->update(['status' => $nextStatus]);

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => 'official_form.submitted',
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
                'event' => 'official_form.version_submitted',
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
}

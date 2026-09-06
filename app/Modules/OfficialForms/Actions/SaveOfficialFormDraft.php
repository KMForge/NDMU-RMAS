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

class SaveOfficialFormDraft
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization,
        private readonly OfficialFormPayloadValidator $payloadValidator = new OfficialFormPayloadValidator,
    ) {}

    /**
     * Save an immutable draft version. The current version is never updated in place.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(User $actor, OfficialFormInstance $instance, array $payload): OfficialFormVersion
    {
        $validatedPayload = $this->payloadValidator->validate(
            strtoupper($instance->definition->code),
            $payload,
        );

        return DB::transaction(function () use ($actor, $instance, $validatedPayload): OfficialFormVersion {
            /** @var OfficialFormInstance $locked */
            $locked = OfficialFormInstance::query()
                ->with(['definition', 'currentVersion'])
                ->lockForUpdate()
                ->findOrFail($instance->getKey());

            if (! in_array($locked->status, ['draft', 'returned_for_correction'], true)) {
                throw new InvalidArgumentException("Form instance #{$locked->id} is not editable from status {$locked->status}.");
            }

            if (! $this->authorization->canSubmit($actor, $locked)) {
                throw new InvalidArgumentException("User #{$actor->id} is not authorized to edit form instance #{$locked->id}.");
            }

            if ($locked->currentVersion?->payload === $validatedPayload) {
                return $locked->currentVersion;
            }

            $isInitialDraft = $locked->currentVersion !== null
                && (int) $locked->currentVersion->version_number === 1
                && $locked->status === 'draft'
                && $locked->currentVersion->signatures()->count() === 0;

            if ($isInitialDraft) {
                $locked->currentVersion->update([
                    'payload' => $validatedPayload,
                    'created_by' => $actor->id,
                ]);

                AuditLog::query()->create([
                    'user_id' => $actor->id,
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'event' => 'official_form.draft_saved',
                    'auditable_type' => OfficialFormInstance::class,
                    'auditable_id' => $locked->id,
                    'description' => "Saved draft version v1 for {$locked->definition->code}.",
                    'subject_snapshot' => [
                        'actor_function' => 'form_editor',
                        'old_status' => $locked->status,
                        'new_status' => 'draft',
                        'version_number' => 1,
                    ],
                ]);

                return $locked->currentVersion;
            }

            $nextNumber = ($locked->versions()->max('version_number') ?? 0) + 1;
            $previous = $locked->currentVersion;
            $oldStatus = $locked->status;

            if ($previous !== null) {
                $locked->versions()->whereKey($previous->id)->update(['is_current' => false]);
            }

            $version = OfficialFormVersion::query()->create([
                'official_form_instance_id' => $locked->id,
                'version_number' => $nextNumber,
                'payload' => $validatedPayload,
                'source_snapshot' => $previous?->source_snapshot,
                'created_by' => $actor->id,
                'supersedes_version_id' => $previous?->id,
                'is_current' => true,
            ]);

            $locked->update([
                'current_version_id' => $version->id,
                'status' => 'draft',
            ]);

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'official_form.draft_saved',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $locked->id,
                'description' => "Saved immutable draft version v{$nextNumber} for {$locked->definition->code}.",
                'subject_snapshot' => [
                    'actor_function' => 'form_editor',
                    'old_status' => $oldStatus,
                    'new_status' => 'draft',
                    'version_number' => $nextNumber,
                ],
            ]);

            return $version;
        });
    }
}

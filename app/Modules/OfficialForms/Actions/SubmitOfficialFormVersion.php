<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitOfficialFormVersion
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(
        User $actor,
        OfficialFormInstance $instance,
        array $payload,
        string $nextStatus = 'submitted'
    ): OfficialFormVersion {
        return DB::transaction(function () use ($actor, $instance, $payload, $nextStatus) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $currentVersion = $lockedInstance->currentVersion;
            $nextVersionNumber = ($lockedInstance->versions()->max('version_number') ?? 0) + 1;

            if ($currentVersion) {
                $lockedInstance->versions()->where('id', $currentVersion->id)->update(['is_current' => false]);
            }

            $newVersion = OfficialFormVersion::query()->create([
                'official_form_instance_id' => $lockedInstance->id,
                'version_number' => $nextVersionNumber,
                'payload' => $payload,
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
            ]);

            return $newVersion;
        });
    }
}

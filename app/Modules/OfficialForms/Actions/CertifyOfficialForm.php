<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CertifyOfficialForm
{
    /**
     * @param  array<string, mixed>  $certificationData
     */
    public function handle(
        User $certifier,
        OfficialFormInstance $instance,
        array $certificationData = []
    ): OfficialFormInstance {
        return DB::transaction(function () use ($certifier, $instance, $certificationData) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $currentVersion = $lockedInstance->currentVersion;
            if ($currentVersion) {
                $payload = array_merge($currentVersion->payload ?? [], [
                    'certified_at' => now()->toIso8601String(),
                    'certified_by' => $certifier->id,
                    'certifier_name' => $certifier->name,
                    'certification_data' => $certificationData,
                ]);
                $currentVersion->update(['payload' => $payload]);
            }

            $lockedInstance->update(['status' => 'completed']);

            AuditLog::query()->create([
                'user_id' => $certifier->id,
                'actor_name' => $certifier->name,
                'actor_email' => $certifier->email,
                'event' => 'official_form.certified',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => "Issued certification for form instance #{$lockedInstance->id} ({$lockedInstance->definition->code}).",
            ]);

            return $lockedInstance->load(['definition', 'currentVersion']);
        });
    }
}

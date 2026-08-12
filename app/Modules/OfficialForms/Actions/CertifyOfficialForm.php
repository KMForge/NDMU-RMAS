<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CertifyOfficialForm
{
    /** @var list<string> */
    private const CERTIFIABLE_FORM_CODES = ['RES-045', 'RES-046'];

    /**
     * @param  array<string, mixed>  $certificationData
     */
    public function handle(
        User $certifier,
        OfficialFormInstance $instance,
        array $certificationData = []
    ): OfficialFormInstance {
        $code = strtoupper($instance->definition->code);
        if (! in_array($code, self::CERTIFIABLE_FORM_CODES, true)) {
            throw new InvalidArgumentException("Form {$code} does not support editing certification.");
        }

        return DB::transaction(function () use ($certifier, $instance, $certificationData, $code) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            if (in_array($lockedInstance->status, ['completed', 'cancelled', 'superseded'], true)) {
                throw new InvalidArgumentException("Form instance #{$lockedInstance->id} cannot be certified from status {$lockedInstance->status}.");
            }

            $this->validateCertifierAuthority($certifier, $lockedInstance, $code);

            // Update instance status only; do NOT mutate submitted version payload
            $lockedInstance->update(['status' => 'completed']);

            AuditLog::query()->create([
                'user_id' => $certifier->id,
                'actor_name' => $certifier->name,
                'actor_email' => $certifier->email,
                'event' => 'official_form.certified',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => "Issued certification for form instance #{$lockedInstance->id} ({$code}).",
                'subject_snapshot' => array_merge($certificationData, [
                    'certified_by' => $certifier->id,
                    'certified_at' => now()->toIso8601String(),
                ]),
            ]);

            return $lockedInstance->load(['definition', 'currentVersion']);
        });
    }

    private function validateCertifierAuthority(User $certifier, OfficialFormInstance $instance, string $code): void
    {
        if ($certifier->can('users.manage')) {
            return;
        }

        $perm = strtolower($code) === 'res-045' ? 'forms.res-045.certify' : 'forms.res-046.certify';
        if (! $certifier->hasPermissionTo($perm)) {
            throw new InvalidArgumentException("User #{$certifier->id} lacks permission [{$perm}].");
        }

        $isAssigned = $instance->actorAssignments()
            ->where('user_id', $certifier->id)
            ->where('status', 'active')
            ->exists() || $instance->initiated_by === $certifier->id;

        if (! $isAssigned) {
            throw new InvalidArgumentException("User #{$certifier->id} is not an assigned editor for form instance #{$instance->id}.");
        }
    }
}

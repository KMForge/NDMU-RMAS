<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormSignatureHasher;
use Illuminate\Support\Facades\DB;

class CompleteAuthorshipCertificate
{
    public function __construct(private readonly OfficialFormSignatureHasher $hasher) {}

    public function handle(OfficialFormInstance $instance, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($instance, $actor): bool {
            $locked = OfficialFormInstance::query()->lockForUpdate()->findOrFail($instance->id);
            if (strtoupper($locked->definition->code) !== 'RES-049'
                || ! in_array($locked->status, ['draft', 'submitted', 'in_progress', 'pending_action'], true)) {
                return false;
            }

            $group = $locked->group;
            $version = $locked->currentVersion;
            if ($group === null || $group->status !== 'active' || $group->disbanded_at !== null || $version === null) {
                return false;
            }

            $required = $group->members()->pluck('student_id')
                ->push($group->leader_student_id)->filter()->unique()->values();
            if ($required->isEmpty()) {
                return false;
            }

            $signed = $version->signatures()
                ->where('official_form_instance_id', $locked->id)
                ->where('actor_type', 'student_researcher')
                ->where('academic_action', 'sign_authorship')
                ->where('version_payload_sha256', $this->hasher->hashVersion($version))
                ->pluck('signer_user_id');
            if ($required->diff($signed)->isNotEmpty()) {
                return false;
            }

            $oldStatus = $locked->status;
            $locked->update(['status' => 'completed']);
            AuditLog::query()->create([
                'user_id' => $actor?->id, 'actor_name' => $actor?->name,
                'actor_email' => $actor?->email,
                'event' => 'official_form.authorship_completed',
                'auditable_type' => OfficialFormInstance::class, 'auditable_id' => $locked->id,
                'description' => 'Completed RES-049 after every required co-author signed the current version.',
                'subject_snapshot' => [
                    'old_status' => $oldStatus, 'new_status' => 'completed',
                    'version_id' => $version->id, 'required_signer_ids' => $required->all(),
                ],
            ]);

            return true;
        });
    }
}

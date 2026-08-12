<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveOfficialForm
{
    /**
     * @param  array<string, mixed>  $approvalMetadata
     */
    public function handle(
        User $approver,
        OfficialFormInstance $instance,
        array $approvalMetadata = [],
        string $targetStatus = 'approved'
    ): OfficialFormInstance {
        return DB::transaction(function () use ($approver, $instance, $approvalMetadata, $targetStatus) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $currentVersion = $lockedInstance->currentVersion;
            if ($currentVersion) {
                $payload = array_merge($currentVersion->payload ?? [], [
                    'approved_at' => now()->toIso8601String(),
                    'approved_by' => $approver->id,
                    'approver_name' => $approver->name,
                    'approval_metadata' => $approvalMetadata,
                ]);
                $currentVersion->update(['payload' => $payload]);
            }

            $lockedInstance->update(['status' => $targetStatus]);

            AuditLog::query()->create([
                'user_id' => $approver->id,
                'actor_name' => $approver->name,
                'actor_email' => $approver->email,
                'event' => 'official_form.approved',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => "Approved form instance #{$lockedInstance->id} ({$lockedInstance->definition->code}).",
            ]);

            return $lockedInstance->load(['definition', 'currentVersion']);
        });
    }
}

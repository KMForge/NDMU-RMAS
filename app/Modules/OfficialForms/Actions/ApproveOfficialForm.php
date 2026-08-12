<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApproveOfficialForm
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization
    ) {}

    /** @var list<string> */
    private const ALLOWED_INITIAL_STATES = ['draft', 'submitted', 'in_progress', 'pending_action'];

    /** @var list<string> */
    private const ALLOWED_TARGET_STATES = ['approved', 'endorsed', 'completed'];

    /**
     * @param  array<string, mixed>  $approvalMetadata
     */
    public function handle(
        User $approver,
        OfficialFormInstance $instance,
        array $approvalMetadata = [],
        string $targetStatus = 'approved'
    ): OfficialFormInstance {
        if (! in_array($targetStatus, self::ALLOWED_TARGET_STATES, true)) {
            throw new InvalidArgumentException("Invalid approval target status [{$targetStatus}].");
        }

        return DB::transaction(function () use ($approver, $instance, $approvalMetadata, $targetStatus) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            if (! in_array($lockedInstance->status, self::ALLOWED_INITIAL_STATES, true)) {
                throw new InvalidArgumentException("Form instance #{$lockedInstance->id} cannot be approved from status {$lockedInstance->status}.");
            }

            if (! $this->authorization->canApprove($approver, $lockedInstance)) {
                throw new InvalidArgumentException("User #{$approver->id} is not contextually authorized to approve form instance #{$instance->id}.");
            }

            // Update instance status only; do NOT mutate submitted version payload
            $lockedInstance->update(['status' => $targetStatus]);

            AuditLog::query()->create([
                'user_id' => $approver->id,
                'actor_name' => $approver->name,
                'actor_email' => $approver->email,
                'event' => 'official_form.approved',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => "Approved form instance #{$lockedInstance->id} ({$lockedInstance->definition->code}) to status {$targetStatus}.",
                'subject_snapshot' => array_merge($approvalMetadata, [
                    'approved_by' => $approver->id,
                    'approved_at' => now()->toIso8601String(),
                ]),
            ]);

            return $lockedInstance->load(['definition', 'currentVersion']);
        });
    }
}

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
    private const ALLOWED_ACTIONS = ['approve', 'reject', 'endorse', 'receive', 'validate', 'sign', 'review', 'respond', 'conforme', 'note'];

    /**
     * @param  array<string, mixed>  $approvalMetadata
     */
    public function handle(
        User $approver,
        OfficialFormInstance $instance,
        array $approvalMetadata,
        string $targetStatus,
        string $action
    ): OfficialFormInstance {
        if (! in_array($action, self::ALLOWED_ACTIONS, true)) {
            throw new InvalidArgumentException("Unsupported official-form action [{$action}].");
        }

        return DB::transaction(function () use ($approver, $instance, $approvalMetadata, $targetStatus, $action) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $transition = $this->authorization->transitionFor($lockedInstance, $action);

            if ($transition === null) {
                throw new InvalidArgumentException("Action [{$action}] is not explicitly configured for form {$lockedInstance->definition->code}.");
            }

            if ($transition['to'] !== $targetStatus) {
                throw new InvalidArgumentException("Action [{$action}] cannot transition form {$lockedInstance->definition->code} to status [{$targetStatus}].");
            }

            if (! in_array($lockedInstance->status, $transition['from'], true)) {
                throw new InvalidArgumentException("Action [{$action}] cannot be performed on form instance #{$lockedInstance->id} from status {$lockedInstance->status}.");
            }

            if (! $this->authorization->canPerformAction($approver, $lockedInstance, $action)) {
                throw new InvalidArgumentException("User #{$approver->id} is not contextually authorized to {$action} form instance #{$instance->id}.");
            }

            $oldStatus = $lockedInstance->status;
            // Update instance status only; do NOT mutate submitted version payload
            $lockedInstance->update(['status' => $targetStatus]);

            AuditLog::query()->create([
                'user_id' => $approver->id,
                'actor_name' => $approver->name,
                'actor_email' => $approver->email,
                'event' => "official_form.{$action}d",
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $lockedInstance->id,
                'description' => ucfirst($action)." action completed for form instance #{$lockedInstance->id} ({$lockedInstance->definition->code}); status changed to {$targetStatus}.",
                'subject_snapshot' => array_merge($approvalMetadata, [
                    'action' => $action,
                    'actor_function' => $this->authorization->requiredActorType($lockedInstance, $action),
                    'old_status' => $oldStatus,
                    'new_status' => $targetStatus,
                    'target_status' => $targetStatus,
                    'acted_by' => $approver->id,
                    'acted_at' => now()->toIso8601String(),
                ]),
            ]);

            return $lockedInstance->load(['definition', 'currentVersion']);
        });
    }
}

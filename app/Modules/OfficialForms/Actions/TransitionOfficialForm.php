<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\OfficialForms\Services\NotifyNextRequiredOfficialForms;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransitionOfficialForm
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization,
        private readonly ApproveOfficialForm $approveAction = new ApproveOfficialForm,
        private readonly CertifyOfficialForm $certifyAction = new CertifyOfficialForm,
        private readonly ActivateAdviserFromInvitation $activateAdviserAction = new ActivateAdviserFromInvitation,
        private readonly ConfirmLanguageEditorAssignment $confirmEditorAction = new ConfirmLanguageEditorAssignment,
        private readonly ReplaceDefensePanelist $replacePanelistAction = new ReplaceDefensePanelist,
        private readonly NotifyNextRequiredOfficialForms $nextFormNotifications = new NotifyNextRequiredOfficialForms,
    ) {
        // Dependencies are injectable so workflow side effects remain testable.
    }

    /**
     * Orchestrates a form transition, executing authorization checks and side effects.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        User $actor,
        OfficialFormInstance $instance,
        string $action,
        string $targetStatus,
        array $metadata = []
    ): OfficialFormInstance {
        return DB::transaction(function () use ($actor, $instance, $action, $targetStatus, $metadata) {
            /** @var OfficialFormInstance $lockedInstance */
            $lockedInstance = OfficialFormInstance::query()
                ->lockForUpdate()
                ->findOrFail($instance->id);

            $code = strtoupper($lockedInstance->definition->code ?? '');

            // Block RES-044 Dean approval transition until threshold confirmation
            if ($code === 'RES-044' && $action === 'approve' && $targetStatus === 'approved') {
                throw new InvalidArgumentException('RES-044 Dean approval transition is blocked pending formal institutional confirmation of rating threshold (4.00).');
            }

            if ($action === 'certify') {
                $updatedInstance = $this->certifyAction->handle($actor, $lockedInstance, $metadata);
            } else {
                $updatedInstance = $this->approveAction->handle($actor, $lockedInstance, $metadata, $targetStatus, $action);
            }

            // Execute single-purpose domain side effects
            if ($code === 'RES-027' && ($action === 'conforme' || $action === 'respond' || $targetStatus === 'conformed' || $targetStatus === 'approved')) {
                $this->activateAdviserAction->handle($updatedInstance, $actor);
            } elseif ($code === 'RES-029' && ($action === 'conforme' || $action === 'respond' || $targetStatus === 'conformed' || $targetStatus === 'approved')) {
                $this->confirmEditorAction->handle($updatedInstance, $actor);
            } elseif ($code === 'RES-030' && in_array($action, ['approve', 'reject'], true)) {
                app(DecideAdviserChangeRequest::class)->handle($actor, $updatedInstance, $targetStatus, $metadata['reviewer_remarks'] ?? null);
                $outgoingPanelistId = $metadata['outgoing_panelist_id'] ?? null;
                $incomingPanelistId = $metadata['incoming_panelist_id'] ?? null;
                if ($outgoingPanelistId && $incomingPanelistId) {
                    $outgoingPanelist = User::query()->findOrFail((int) $outgoingPanelistId);
                    $incomingPanelist = User::query()->findOrFail((int) $incomingPanelistId);
                    $this->replacePanelistAction->handle($updatedInstance, $outgoingPanelist, $incomingPanelist, $actor);
                }
            }

            AuditLog::query()->create([
                'user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_email' => $actor->email,
                'event' => 'official_form.transitioned',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $updatedInstance->id,
                'description' => "Transitioned form instance #{$updatedInstance->id} ({$code}) via action [{$action}] to status [{$targetStatus}].",
            ]);

            $this->nextFormNotifications->handle($actor, $updatedInstance);

            return $updatedInstance->load(['definition', 'currentVersion']);
        });
    }
}

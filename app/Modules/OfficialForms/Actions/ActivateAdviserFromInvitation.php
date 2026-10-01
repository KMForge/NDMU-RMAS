<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\AuditLog;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ActivateAdviserFromInvitation
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    public function handle(OfficialFormInstance $instance, User $adviser): void
    {
        $code = strtoupper($instance->definition->code ?? '');
        if ($code !== 'RES-027') {
            throw new InvalidArgumentException("ActivateAdviserFromInvitation requires RES-027 instance, given {$code}.");
        }

        DB::transaction(function () use ($instance, $adviser) {
            $group = ResearchClassGroup::query()->lockForUpdate()->findOrFail($instance->research_class_group_id);
            $adviserRequest = ResearchClassGroupAdviserRequest::query()
                ->whereKey($instance->source_id)
                ->where('research_class_group_id', $group->id)
                ->where('adviser_id', $adviser->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->lockForUpdate()
                ->first();

            if ($instance->source_type !== ResearchClassGroupAdviserRequest::class || $adviserRequest === null) {
                throw new InvalidArgumentException('RES-027 is not linked to an active invitation for this adviser.');
            }

            if (! $adviser->isActiveAndApproved() || $adviser->email_verified_at === null || ! $adviser->can('classes.serve-as-adviser')) {
                throw new InvalidArgumentException('The invited adviser is not active and eligible to serve as an adviser.');
            }
            if ($group->adviser_id !== null && (int) $group->adviser_id !== (int) $adviser->id) {
                throw new InvalidArgumentException('An active adviser can only be changed through an approved RES-030 Adviser Change Request Form.');
            }
            if ((int) $group->adviser_id === (int) $adviser->id) {
                if ($adviserRequest->status === 'pending') {
                    $adviserRequest->update([
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }

                return;
            }

            $group->update(['adviser_id' => $adviser->id]);
            $adviserRequest->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            // Create new active adviser history record
            ResearchClassGroupAdviserHistory::query()->create([
                'research_class_group_id' => $group->id,
                'adviser_id' => $adviser->id,
                'assigned_by' => $adviserRequest->requested_by,
                'assigned_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $adviser->id,
                'actor_name' => $adviser->name,
                'actor_email' => $adviser->email,
                'event' => 'adviser.activated_from_invitation',
                'auditable_type' => ResearchClassGroup::class,
                'auditable_id' => $group->id,
                'description' => "Adviser {$adviser->name} activated assignment for research group #{$group->id} via RES-027 conforme.",
            ]);

            $coordinator = User::query()->find($adviserRequest->requested_by);
            if ($coordinator !== null) {
                $this->notifications->send(
                    recipient: $coordinator,
                    eventKey: 'adviser.invitation.accepted',
                    title: 'Adviser invitation accepted',
                    message: "{$adviser->name} signed RES-027 and accepted the invitation for {$group->name}.",
                    category: 'class',
                    routeName: 'facilitator.classes.show',
                    routeParameters: ['researchClass' => $group->research_class_id],
                    sourceType: ResearchClassGroupAdviserRequest::class,
                    sourceId: $adviserRequest->getKey(),
                    actor: $adviser,
                    contextLabel: $group->name,
                    actingAs: 'Program Coordinator',
                    occurrence: 'accepted',
                );
            }
        });
    }
}

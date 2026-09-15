<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchGroupAdviserChangeRequest;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DecideAdviserChangeRequest
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function handle(User $reviewer, OfficialFormInstance $instance, string $decision, ?string $remarks = null): ResearchGroupAdviserChangeRequest
    {
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('The adviser change decision must be approved or rejected.');
        }
        if (strtoupper((string) $instance->definition?->code) !== 'RES-030') {
            throw new InvalidArgumentException('This decision action only accepts RES-030 adviser change requests.');
        }
        $action = $decision === 'approved' ? 'approve' : 'reject';
        if (! $this->authorization->canPerformAction($reviewer, $instance, $action)) {
            throw new InvalidArgumentException('You are not authorized to decide this adviser change request.');
        }
        $remarks = trim((string) $remarks);
        if ($decision === 'rejected' && $remarks === '') {
            throw new InvalidArgumentException('Reviewer remarks are required when rejecting an adviser change request.');
        }

        return DB::transaction(function () use ($reviewer, $instance, $decision, $remarks): ResearchGroupAdviserChangeRequest {
            $lockedInstance = OfficialFormInstance::query()->lockForUpdate()->findOrFail($instance->id);
            if ($lockedInstance->status !== $decision) {
                throw new InvalidArgumentException("RES-030 must first record the {$decision} form decision before applying its adviser-change effect.");
            }

            $changeRequest = ResearchGroupAdviserChangeRequest::query()
                ->with(['previousAdviser', 'requestedAdviser'])
                ->where('official_form_instance_id', $instance->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($changeRequest->status !== 'submitted') {
                throw new InvalidArgumentException('This adviser change request has already received a final decision.');
            }

            $group = ResearchClassGroup::query()->lockForUpdate()->findOrFail($changeRequest->research_class_group_id);
            if ((int) $group->adviser_id !== (int) $changeRequest->previous_adviser_id) {
                throw new InvalidArgumentException('The group adviser changed after this request was submitted. Review the current assignment before deciding it.');
            }

            $effectiveAt = null;
            if ($decision === 'approved') {
                $incoming = User::query()->lockForUpdate()->findOrFail($changeRequest->requested_adviser_id);
                if (! $incoming->isActiveAndApproved() || $incoming->email_verified_at === null || ! $incoming->can('classes.serve-as-adviser')) {
                    throw new InvalidArgumentException('The requested new adviser is no longer active and eligible.');
                }

                $effectiveAt = now();
                ResearchClassGroupAdviserHistory::query()
                    ->where('research_class_group_id', $group->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => $effectiveAt, 'ended_by' => $reviewer->id, 'updated_at' => $effectiveAt]);
                $group->update(['adviser_id' => $incoming->id]);
                ResearchClassGroupAdviserHistory::query()->create([
                    'research_class_group_id' => $group->id,
                    'adviser_id' => $incoming->id,
                    'assigned_by' => $reviewer->id,
                    'assigned_at' => $effectiveAt,
                ]);
            }

            $changeRequest->update([
                'status' => $decision,
                'reviewed_by' => $reviewer->id,
                'reviewer_remarks' => $remarks !== '' ? $remarks : null,
                'reviewed_at' => now(),
                'effective_at' => $effectiveAt,
            ]);

            $this->auditLogs->write(
                actor: $reviewer,
                event: "adviser.change-request.{$decision}",
                description: ucfirst($decision)." the adviser change request for {$group->name}.",
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $changeRequest,
                subjectName: $group->name,
                oldValues: [
                    'request_status' => 'submitted',
                    'adviser_id' => $changeRequest->previous_adviser_id,
                    'adviser_name' => $changeRequest->previousAdviser?->name,
                ],
                newValues: [
                    'request_status' => $decision,
                    'adviser_id' => $decision === 'approved' ? $changeRequest->requested_adviser_id : $changeRequest->previous_adviser_id,
                    'adviser_name' => $decision === 'approved' ? $changeRequest->requestedAdviser?->name : $changeRequest->previousAdviser?->name,
                    'requested_adviser_id' => $changeRequest->requested_adviser_id,
                    'requested_adviser_name' => $changeRequest->requestedAdviser?->name,
                    'reviewer_id' => $reviewer->id,
                    'reviewer_name' => $reviewer->name,
                    'reviewer_remarks' => $remarks !== '' ? $remarks : null,
                    'effective_at' => $effectiveAt?->toIso8601String(),
                ],
                actorContext: 'authorized-adviser-change-reviewer',
            );

            return $changeRequest->refresh();
        }, 3);
    }
}

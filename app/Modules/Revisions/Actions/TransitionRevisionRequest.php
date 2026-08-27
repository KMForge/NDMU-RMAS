<?php

namespace App\Modules\Revisions\Actions;

use App\Enums\RevisionStatus;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use App\Notifications\RevisionStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransitionRevisionRequest
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function start(
        User $student,
        RevisionRequest $revisionRequest,
        ?string $ipAddress,
    ): RevisionRequest {
        return $this->transition(
            $student,
            $revisionRequest,
            [RevisionStatus::Open],
            RevisionStatus::InProgress,
            'started',
            null,
            $ipAddress,
        );
    }

    public function resolve(
        User $adviser,
        RevisionRequest $revisionRequest,
        ?string $notes,
        ?string $ipAddress,
    ): RevisionRequest {
        return $this->transition(
            $adviser,
            $revisionRequest,
            [RevisionStatus::Submitted],
            RevisionStatus::Resolved,
            'resolved',
            $notes,
            $ipAddress,
        );
    }

    public function reopen(
        User $adviser,
        RevisionRequest $revisionRequest,
        ?string $notes,
        ?string $ipAddress,
    ): RevisionRequest {
        return $this->transition(
            $adviser,
            $revisionRequest,
            [RevisionStatus::Submitted, RevisionStatus::Resolved],
            RevisionStatus::Open,
            'reopened',
            $notes,
            $ipAddress,
        );
    }

    /**
     * @param  array<int, RevisionStatus>  $allowedFrom
     */
    private function transition(
        User $actor,
        RevisionRequest $revisionRequest,
        array $allowedFrom,
        RevisionStatus $to,
        string $action,
        ?string $notes,
        ?string $ipAddress,
    ): RevisionRequest {
        return DB::transaction(function () use (
            $actor,
            $revisionRequest,
            $allowedFrom,
            $to,
            $action,
            $notes,
            $ipAddress,
        ): RevisionRequest {
            $lockedRevision = RevisionRequest::query()
                ->whereKey($revisionRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $from = $lockedRevision->status;

            if (! in_array($from, $allowedFrom, true)) {
                throw new RevisionWorkflowException(
                    'This revision request cannot be changed from its current status.',
                );
            }

            $safeNotes = Str::limit(trim(strip_tags((string) $notes)), 5000, '');
            $lockedRevision->update([
                'status' => $to,
                'resolved_at' => $to === RevisionStatus::Resolved ? now() : null,
            ]);

            RevisionRequestEvent::query()->create([
                'revision_request_id' => $lockedRevision->getKey(),
                'actor_id' => $actor->getKey(),
                'action' => $action,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'notes' => $safeNotes !== '' ? $safeNotes : null,
                'ip_address' => $this->safeIpAddress($ipAddress),
                'occurred_at' => now(),
            ]);

            $this->auditLogs->write(
                actor: $actor,
                event: match ($action) {
                    'started' => 'revision.started',
                    'resolved' => 'revision.resolved',
                    'reopened' => 'revision.reopened',
                    default => 'revision.transitioned',
                },
                description: 'A revision request changed workflow status.',
                requestContext: new AuditRequestContext($this->safeIpAddress($ipAddress), null, null),
                auditable: $lockedRevision,
                subjectName: 'Revision request #'.$lockedRevision->getKey(),
                oldValues: ['status' => $from->value],
                newValues: ['status' => $to->value],
                actorContext: $action === 'started' ? 'student-researcher' : 'thesis-adviser',
            );

            // Legacy / Disabled: Direct notifications remain Phase 23
            // $this->notifyCounterparty($lockedRevision, $actor, $action);

            return $lockedRevision->fresh([
                'assignee:id,name,email',
                'requester:id,name,email',
                'submittedDocuments',
                'events',
            ]);
        }, 3);
    }

    private function notifyCounterparty(
        RevisionRequest $revisionRequest,
        User $actor,
        string $action,
    ): void {
        $recipientId = $actor->getKey() === $revisionRequest->assigned_to
            ? $revisionRequest->requested_by
            : $revisionRequest->assigned_to;

        $recipient = User::query()->find($recipientId);
        $recipient?->notify(new RevisionStatusChanged($revisionRequest, $actor, $action));
    }

    private function safeIpAddress(?string $ipAddress): ?string
    {
        if ($ipAddress === null || filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ipAddress;
    }
}

<?php

namespace App\Modules\Revisions\Actions;

use App\Enums\RevisionStatus;
use App\Models\DocumentReviewComment;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ResolveRevisionCycle
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    public function handle(User $user, RevisionRequest $revisionRequest, ?string $notes = null): RevisionRequest
    {
        return DB::transaction(function () use ($user, $revisionRequest, $notes): RevisionRequest {
            $lockedRevision = RevisionRequest::query()
                ->with(['researchClassGroup', 'sourceDocument'])
                ->whereKey($revisionRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $group = $lockedRevision->researchClassGroup;

            if ($group === null || ! $group->isActive()) {
                throw new RevisionWorkflowException('The research group associated with this revision cycle is not active.');
            }

            if ($group->adviser_id !== $user->getKey()) {
                throw new AuthorizationException('Only the current assigned adviser of this research group can resolve this revision cycle.');
            }

            if ($lockedRevision->status !== RevisionStatus::Submitted) {
                throw new RevisionWorkflowException('Only submitted revision cycles can be marked as resolved.');
            }

            $sourceDocId = $lockedRevision->document_id;

            if ($sourceDocId !== null) {
                $hasUnresolvedBlockingFindings = DocumentReviewComment::query()
                    ->where('document_id', $sourceDocId)
                    ->whereIn('severity', ['revision', 'critical'])
                    ->whereNull('resolved_at')
                    ->exists();

                if ($hasUnresolvedBlockingFindings) {
                    throw new RevisionWorkflowException('Cannot resolve revision cycle while source document has unresolved revision or critical findings.');
                }
            }

            $fromStatus = $lockedRevision->status;
            $lockedRevision->update([
                'status' => RevisionStatus::Resolved,
                'resolved_at' => now(),
            ]);

            RevisionRequestEvent::query()->create([
                'revision_request_id' => $lockedRevision->getKey(),
                'actor_id' => $user->getKey(),
                'document_id' => $lockedRevision->submitted_document_id ?? $lockedRevision->document_id,
                'action' => 'resolved',
                'from_status' => $fromStatus->value,
                'to_status' => RevisionStatus::Resolved->value,
                'notes' => $notes ?? 'Adviser verified revision resolution.',
                'occurred_at' => now(),
                'metadata' => [
                    'adviser_id' => $user->getKey(),
                    'resolved_at' => now()->toIso8601String(),
                ],
            ]);

            $students = $group->members()->with('student')->get()->pluck('student')->filter();
            $this->notifications->sendToMany(
                recipients: $students,
                eventKey: 'revision.resolved',
                title: 'Revision resolved',
                message: "{$lockedRevision->title} was resolved by {$user->name}.",
                category: 'revision',
                routeName: 'student.dashboard',
                routeParameters: ['tab' => 'revisions'],
                sourceType: RevisionRequest::class,
                sourceId: $lockedRevision->getKey(),
                actor: $user,
                contextLabel: $group->name,
                actingAs: 'Student Researcher',
                occurrence: RevisionStatus::Resolved->value,
            );

            return $lockedRevision;
        }, 3);
    }
}

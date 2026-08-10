<?php

namespace App\Modules\Revisions\Actions;

use App\Enums\RevisionStatus;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ReopenRevisionCycle
{
    public function handle(User $user, RevisionRequest $revisionRequest, string $reason): RevisionRequest
    {
        if (trim($reason) === '') {
            throw new RevisionWorkflowException('A valid reason is required to correct an accidental resolution.');
        }

        return DB::transaction(function () use ($user, $revisionRequest, $reason): RevisionRequest {
            $lockedRevision = RevisionRequest::query()
                ->with(['researchClassGroup'])
                ->whereKey($revisionRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $group = $lockedRevision->researchClassGroup;

            if ($group === null || ! $group->isActive()) {
                throw new RevisionWorkflowException('The research group for this revision request is not active.');
            }

            if ($group->adviser_id !== $user->getKey()) {
                throw new AuthorizationException('Only the current assigned adviser can perform controlled reopening.');
            }

            if ($lockedRevision->status !== RevisionStatus::Resolved) {
                throw new RevisionWorkflowException('Controlled reopening is only permitted for accidentally resolved revision cycles.');
            }

            $fromStatus = $lockedRevision->status;
            $lockedRevision->update([
                'status' => RevisionStatus::Submitted,
                'resolved_at' => null,
            ]);

            RevisionRequestEvent::query()->create([
                'revision_request_id' => $lockedRevision->getKey(),
                'actor_id' => $user->getKey(),
                'document_id' => $lockedRevision->submitted_document_id ?? $lockedRevision->document_id,
                'action' => 'controlled_reopened',
                'from_status' => $fromStatus->value,
                'to_status' => RevisionStatus::Submitted->value,
                'notes' => 'Adviser performed controlled reopening to correct accidental resolution. Reason: '.trim($reason),
                'occurred_at' => now(),
                'metadata' => [
                    'reason' => trim($reason),
                    'previous_resolved_at' => $lockedRevision->getOriginal('resolved_at'),
                    'adviser_id' => $user->getKey(),
                    'submitted_document_id' => $lockedRevision->submitted_document_id,
                ],
            ]);

            return $lockedRevision;
        }, 3);
    }
}

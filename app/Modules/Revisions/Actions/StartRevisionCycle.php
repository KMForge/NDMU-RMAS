<?php

namespace App\Modules\Revisions\Actions;

use App\Enums\RevisionStatus;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class StartRevisionCycle
{
    public function handle(User $user, RevisionRequest $revisionRequest): RevisionRequest
    {
        return DB::transaction(function () use ($user, $revisionRequest): RevisionRequest {
            $lockedRevision = RevisionRequest::query()
                ->with(['researchClassGroup', 'sourceDocument'])
                ->whereKey($revisionRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $group = $lockedRevision->researchClassGroup;

            if ($group === null || ! $group->isActive()) {
                throw new RevisionWorkflowException('The research group associated with this revision cycle is not active.');
            }

            if (! $group->isLeader($user)) {
                throw new AuthorizationException('Only your assigned Group Leader can start official revision work.');
            }

            if ($lockedRevision->status !== RevisionStatus::Open) {
                throw new RevisionWorkflowException('Only open revision cycles can be started.');
            }

            $fromStatus = $lockedRevision->status;
            $lockedRevision->update(['status' => RevisionStatus::InProgress]);

            RevisionRequestEvent::query()->create([
                'revision_request_id' => $lockedRevision->getKey(),
                'actor_id' => $user->getKey(),
                'document_id' => $lockedRevision->document_id,
                'action' => 'started',
                'from_status' => $fromStatus->value,
                'to_status' => RevisionStatus::InProgress->value,
                'notes' => 'Group leader started revision work.',
                'occurred_at' => now(),
                'metadata' => [
                    'group_id' => $group->getKey(),
                    'leader_id' => $user->getKey(),
                ],
            ]);

            return $lockedRevision;
        }, 3);
    }
}

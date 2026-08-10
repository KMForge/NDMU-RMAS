<?php

namespace App\Modules\Revisions\Actions;

use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateRevisionDueDate
{
    public function handle(User $user, RevisionRequest $revisionRequest, ?string $dueAt = null): RevisionRequest
    {
        return DB::transaction(function () use ($user, $revisionRequest, $dueAt): RevisionRequest {
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
                throw new AuthorizationException('Only the current assigned adviser can update the revision due date.');
            }

            $oldDueAt = $lockedRevision->due_at;
            $newDueAt = $dueAt !== null && trim($dueAt) !== '' ? CarbonImmutable::parse($dueAt) : null;

            $lockedRevision->update(['due_at' => $newDueAt]);

            RevisionRequestEvent::query()->create([
                'revision_request_id' => $lockedRevision->getKey(),
                'actor_id' => $user->getKey(),
                'document_id' => $lockedRevision->document_id,
                'action' => 'due_date_changed',
                'from_status' => $lockedRevision->status->value,
                'to_status' => $lockedRevision->status->value,
                'notes' => 'Adviser updated revision due date.',
                'occurred_at' => now(),
                'metadata' => [
                    'old_due_at' => $oldDueAt?->toIso8601String(),
                    'new_due_at' => $newDueAt?->toIso8601String(),
                    'adviser_id' => $user->getKey(),
                ],
            ]);

            return $lockedRevision;
        }, 3);
    }
}

<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RespondResearchGroupAdviserRequest
{
    public function handle(
        User $adviser,
        ResearchClassGroupAdviserRequest $adviserRequest,
        string $decision, // 'accept' or 'decline'
    ): ResearchClassGroupAdviserRequest {
        if (! $adviser->can('classes.serve-as-adviser') || ! $adviser->isActiveAndApproved()) {
            throw new ClassOperationException('Only active and approved advisers may respond to requests.');
        }

        if (! in_array($decision, ['accept', 'decline'], true)) {
            throw new ClassOperationException('Invalid request response decision.');
        }

        try {
            return DB::transaction(function () use ($adviser, $adviserRequest, $decision): ResearchClassGroupAdviserRequest {
                $lockedRequest = ResearchClassGroupAdviserRequest::query()
                    ->whereKey($adviserRequest->getKey())
                    ->where('adviser_id', $adviser->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($lockedRequest === null) {
                    throw new ClassOperationException('The pending adviser request was not found.');
                }

                $group = ResearchClassGroup::query()
                    ->whereKey($lockedRequest->research_class_group_id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($group === null) {
                    throw new ClassOperationException('The research group is no longer active.');
                }

                $now = now();

                if ($decision === 'accept') {
                    if ($group->adviser_id !== null) {
                        throw new ClassOperationException('This group already has an active adviser.');
                    }

                    $lockedRequest->update([
                        'status' => 'accepted',
                        'responded_at' => $now,
                    ]);

                    $group->update([
                        'adviser_id' => $adviser->getKey(),
                    ]);

                    ResearchClassGroupAdviserHistory::query()->create([
                        'research_class_group_id' => $group->getKey(),
                        'adviser_id' => $adviser->getKey(),
                        'assigned_by' => $lockedRequest->requested_by,
                        'assigned_at' => $now,
                    ]);
                } else {
                    $lockedRequest->update([
                        'status' => 'declined',
                        'responded_at' => $now,
                    ]);
                }

                return $lockedRequest->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The response could not be processed. Please try again.');
        }
    }
}

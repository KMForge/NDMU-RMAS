<?php

namespace App\Modules\Classes\Actions;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RespondResearchGroupAdviserRequest
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

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

                if ($decision === 'accept') {
                    if ($group->adviser_id !== null) {
                        throw new ClassOperationException('This group already has an active adviser.');
                    }

                    $hasInvitation = OfficialFormInstance::query()
                        ->where('source_type', ResearchClassGroupAdviserRequest::class)
                        ->where('source_id', $lockedRequest->getKey())
                        ->whereHas('definition', fn ($query) => $query->where('code', 'RES-027'))
                        ->whereHas('actorAssignments', fn ($query) => $query
                            ->where('user_id', $adviser->getKey())
                            ->where('actor_type', 'adviser')
                            ->where('status', 'active'))
                        ->exists();

                    if (! $hasInvitation) {
                        throw new ClassOperationException('The RES-027 adviser invitation is missing. Ask the Program Coordinator to resend the request.');
                    }

                    return $lockedRequest;
                }

                $lockedRequest->update([
                    'status' => 'declined',
                    'responded_at' => now(),
                ]);

                OfficialFormInstance::query()
                    ->where('source_type', ResearchClassGroupAdviserRequest::class)
                    ->where('source_id', $lockedRequest->getKey())
                    ->whereHas('definition', fn ($query) => $query->where('code', 'RES-027'))
                    ->each(function (OfficialFormInstance $instance): void {
                        $instance->update(['status' => 'rejected']);
                        $instance->actorAssignments()->where('status', 'active')->update(['status' => 'inactive']);
                    });

                $coordinator = User::query()->find($lockedRequest->requested_by);

                if ($coordinator !== null) {
                    $decisionLabel = 'declined';
                    $this->notifications->send(
                        recipient: $coordinator,
                        eventKey: "adviser.invitation.{$decisionLabel}",
                        title: "Adviser invitation {$decisionLabel}",
                        message: "{$adviser->name} {$decisionLabel} the invitation for {$group->name}.",
                        category: 'class',
                        routeName: 'facilitator.dashboard',
                        routeParameters: ['tab' => 'classes'],
                        sourceType: ResearchClassGroupAdviserRequest::class,
                        sourceId: $lockedRequest->getKey(),
                        actor: $adviser,
                        contextLabel: $group->name,
                        actingAs: 'Program Coordinator',
                        occurrence: $decision,
                    );
                }

                return $lockedRequest->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The response could not be processed. Please try again.');
        }
    }
}

<?php

namespace App\Modules\Classes\Actions;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CancelResearchClassGroupAdviserRequest
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
        private readonly InstitutionalActorResolver $institutionalActors,
    ) {}

    public function handle(
        User $coordinator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResearchClassGroupAdviserRequest $adviserRequest,
    ): void {
        try {
            DB::transaction(function () use ($coordinator, $researchClass, $group, $adviserRequest): void {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->lockForUpdate()
                    ->first();

                $lockedRequest = ResearchClassGroupAdviserRequest::query()
                    ->whereKey($adviserRequest->getKey())
                    ->where('research_class_group_id', $group->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null || $lockedRequest === null) {
                    throw new ClassOperationException('The pending adviser request was not found.');
                }

                $lockedClass->loadMissing('facilitator.facultyProfile', 'groups.members.student.studentProfile.program');
                $lockedGroup->loadMissing('researchClass', 'members.student.studentProfile.program');
                if (! $this->institutionalActors->isProgramCoordinator($coordinator, $lockedClass, $lockedGroup)
                    || ! $coordinator->can('classes.assign-advisers')) {
                    throw new AuthorizationException('Only the authorized Program Coordinator may cancel adviser invitations for this class.');
                }

                $lockedRequest->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                OfficialFormInstance::query()
                    ->where('source_type', ResearchClassGroupAdviserRequest::class)
                    ->where('source_id', $lockedRequest->getKey())
                    ->whereHas('definition', fn ($query) => $query->where('code', 'RES-027'))
                    ->each(function (OfficialFormInstance $instance): void {
                        $instance->update(['status' => 'cancelled']);
                        $instance->actorAssignments()->where('status', 'active')->update(['status' => 'inactive']);
                    });

                $recipient = User::query()->find($lockedRequest->adviser_id);
                if ($recipient !== null) {
                    $this->notifications->send(
                        recipient: $recipient,
                        eventKey: 'adviser.invitation.cancelled',
                        title: 'Adviser invitation cancelled',
                        message: "The adviser invitation for {$lockedGroup->name} was cancelled.",
                        category: 'class',
                        routeName: 'adviser.dashboard',
                        routeParameters: ['tab' => 'classes'],
                        sourceType: ResearchClassGroupAdviserRequest::class,
                        sourceId: $lockedRequest->getKey(),
                        actor: $coordinator,
                        contextLabel: $lockedGroup->name,
                        actingAs: 'Thesis Adviser',
                        occurrence: 'cancelled',
                    );
                }
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The adviser request could not be cancelled. Please try again.');
        }
    }
}

<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RequestAdviserForResearchClassGroup
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
        private readonly CreateOfficialFormInstance $createOfficialForm,
        private readonly InstitutionalActorResolver $institutionalActors,
    ) {}

    public function handle(
        User $coordinator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        User $adviser,
    ): ResearchClassGroupAdviserRequest {
        if (! $adviser->can('classes.serve-as-adviser') || ! $adviser->isActiveAndApproved()) {
            throw new ClassOperationException('Select an active and approved research adviser.');
        }

        try {
            return DB::transaction(function () use ($coordinator, $researchClass, $group, $adviser): ResearchClassGroupAdviserRequest {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null) {
                    throw new ClassOperationException('The class group was not found.');
                }

                $lockedClass->loadMissing('facilitator.facultyProfile', 'groups.members.student.studentProfile.program');
                $lockedGroup->loadMissing('researchClass', 'members.student.studentProfile.program');
                if (! $this->institutionalActors->isProgramCoordinator($coordinator, $lockedClass, $lockedGroup)
                    || ! $coordinator->can('classes.assign-advisers')) {
                    throw new AuthorizationException('Only the authorized Program Coordinator may issue adviser invitations for this class.');
                }

                if ($lockedGroup->adviser_id !== null) {
                    throw new ClassOperationException('This group already has an active adviser. Remove the current adviser first.');
                }

                $hasPendingRequest = ResearchClassGroupAdviserRequest::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->exists();

                if ($hasPendingRequest) {
                    throw new ClassOperationException('This group already has a pending adviser request.');
                }

                $request = ResearchClassGroupAdviserRequest::query()->create([
                    'research_class_group_id' => $lockedGroup->getKey(),
                    'adviser_id' => $adviser->getKey(),
                    'requested_by' => $coordinator->getKey(),
                    'status' => 'pending',
                    'requested_at' => now(),
                ]);

                $lockedGroup->loadMissing([
                    'researchClass',
                    'researchGroup.currentProject',
                ]);

                $invitation = $this->createOfficialForm->handle(
                    initiator: $coordinator,
                    formCode: 'RES-027',
                    groupId: $lockedGroup->getKey(),
                    contextKey: 'adviser-request-'.$request->getKey(),
                    sourceType: ResearchClassGroupAdviserRequest::class,
                    sourceId: $request->getKey(),
                    actorUserId: $adviser->getKey(),
                    payload: [
                        'date' => now()->format('Y-m-d'),
                        'course' => (string) $lockedClass->name,
                        'research_title' => (string) ($lockedGroup->researchGroup?->currentProject?->title
                            ?? ''),
                    ],
                );

                $this->notifications->send(
                    recipient: $adviser,
                    eventKey: 'adviser.invitation.received',
                    title: 'Adviser invitation received',
                    message: "You were invited to advise {$lockedGroup->name}.",
                    category: 'class',
                    routeName: 'official-forms.workspace.show',
                    routeParameters: ['instance' => $invitation->getKey()],
                    sourceType: ResearchClassGroupAdviserRequest::class,
                    sourceId: $request->getKey(),
                    actor: $coordinator,
                    contextLabel: $lockedGroup->name,
                    actingAs: 'Thesis Adviser',
                );

                return $request;
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The adviser request could not be sent. Please try again.');
        }
    }
}

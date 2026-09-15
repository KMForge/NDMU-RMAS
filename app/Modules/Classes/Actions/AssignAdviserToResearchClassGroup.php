<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AssignAdviserToResearchClassGroup
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        User $adviser,
    ): ResearchClassGroup {
        if (! $adviser->can('classes.serve-as-adviser') || ! $adviser->isActiveAndApproved()) {
            throw new ClassOperationException('Select an active and approved research adviser.');
        }

        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $group, $adviser): ResearchClassGroup {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new ClassOperationException('You cannot assign advisers for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null) {
                    throw new ClassOperationException('The class group was not found.');
                }

                $previousAdviserId = $lockedGroup->adviser_id;
                if ($previousAdviserId !== null && (int) $previousAdviserId !== (int) $adviser->getKey()) {
                    throw new ClassOperationException('An active adviser can only be changed through an approved RES-030 Adviser Change Request Form.');
                }
                if ((int) $previousAdviserId === (int) $adviser->getKey()) {
                    return $lockedGroup;
                }
                $lockedGroup->update(['adviser_id' => $adviser->getKey()]);

                $this->notifications->send(
                    recipient: $adviser,
                    eventKey: 'adviser.assignment.created',
                    title: 'Research group assigned',
                    message: "You were assigned as Thesis Adviser for {$lockedGroup->name}.",
                    category: 'class',
                    routeName: 'adviser.dashboard',
                    routeParameters: ['tab' => 'classes'],
                    sourceType: ResearchClassGroup::class,
                    sourceId: $lockedGroup->getKey(),
                    actor: $facilitator,
                    contextLabel: $lockedGroup->name,
                    actingAs: 'Thesis Adviser',
                    occurrence: 'assigned',
                );

                $this->auditLogs->write(
                    actor: $facilitator,
                    event: 'adviser.assignment.created',
                    description: 'A thesis adviser was assigned to a research group.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $lockedGroup,
                    subjectName: $lockedGroup->name,
                    oldValues: ['adviser_id' => $previousAdviserId],
                    newValues: ['adviser_id' => $adviser->getKey()],
                    actorContext: 'research-facilitator',
                );

                return $lockedGroup->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The adviser could not be assigned. Please try again.');
        }
    }
}

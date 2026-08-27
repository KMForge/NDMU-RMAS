<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RemoveResearchClassGroupAdviser
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
    ): void {
        try {
            DB::transaction(function () use ($facilitator, $researchClass, $group): void {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new AuthorizationException('You cannot manage advisers for this class.');
                }

                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null || $lockedGroup->adviser_id === null) {
                    throw new ClassOperationException('The group does not currently have an active adviser.');
                }

                $now = now();
                $removedAdviserId = $lockedGroup->adviser_id;

                ResearchClassGroupAdviserHistory::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->whereNull('ended_at')
                    ->update([
                        'ended_at' => $now,
                        'ended_by' => $facilitator->getKey(),
                        'updated_at' => $now,
                    ]);

                $lockedGroup->update([
                    'adviser_id' => null,
                ]);

                $removedAdviser = User::query()->find($removedAdviserId);
                if ($removedAdviser !== null) {
                    $this->notifications->send(
                        recipient: $removedAdviser,
                        eventKey: 'adviser.assignment.removed',
                        title: 'Adviser assignment ended',
                        message: "Your adviser assignment for {$lockedGroup->name} has ended.",
                        category: 'class',
                        routeName: 'adviser.dashboard',
                        routeParameters: ['tab' => 'classes'],
                        sourceType: ResearchClassGroup::class,
                        sourceId: $lockedGroup->getKey(),
                        actor: $facilitator,
                        contextLabel: $lockedGroup->name,
                        actingAs: 'Thesis Adviser',
                        occurrence: $now->toIso8601String(),
                    );
                }
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The adviser could not be removed. Please try again.');
        }
    }
}

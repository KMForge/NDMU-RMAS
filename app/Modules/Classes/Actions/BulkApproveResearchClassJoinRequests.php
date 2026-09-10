<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BulkApproveResearchClassJoinRequests
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    /**
     * @param  array<int, int>  $joinRequestIds
     * @return Collection<int, ResearchClassEnrollment>
     */
    public function handle(User $facilitator, array $joinRequestIds): Collection
    {
        $ids = collect($joinRequestIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values();

        try {
            return DB::transaction(function () use ($facilitator, $ids): Collection {
                $requests = ResearchClassEnrollment::query()
                    ->whereKey($ids)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($requests->count() !== $ids->count()) {
                    throw new ClassOperationException('One or more selected join requests could not be found. Refresh the page and try again.');
                }

                if ($requests->contains(fn (ResearchClassEnrollment $request): bool => $request->status !== 'pending')) {
                    throw new DuplicateClassOperation('One or more selected join requests have already been reviewed. Refresh the page and try again.');
                }

                $classes = ResearchClass::query()
                    ->whereKey($requests->pluck('research_class_id')->unique()->sort()->values())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($requests->groupBy('research_class_id') as $classId => $classRequests) {
                    $researchClass = $classes->get($classId);

                    if ($researchClass === null || $researchClass->facilitator_id !== $facilitator->getKey()) {
                        throw new ClassOperationException('One or more selected join requests do not belong to your classes.');
                    }

                    if (! $researchClass->is_active) {
                        throw new ClassOperationException("{$researchClass->name} is no longer available.");
                    }

                    $activeStudents = ResearchClassEnrollment::query()
                        ->where('research_class_id', $researchClass->getKey())
                        ->where('status', 'active')
                        ->count();
                    $availableSeats = max(0, $researchClass->max_students - $activeStudents);

                    if ($classRequests->count() > $availableSeats) {
                        throw new ClassOperationException(
                            "{$researchClass->name} only has {$availableSeats} available ".str('seat')->plural($availableSeats).'. Reduce the selection and try again.',
                        );
                    }
                }

                $students = User::query()
                    ->whereKey($requests->pluck('student_id')->unique()->sort()->values())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $alreadyEnrolledStudentIds = ResearchClassEnrollment::query()
                    ->whereIn('student_id', $requests->pluck('student_id'))
                    ->where('status', 'active')
                    ->pluck('student_id');

                if ($alreadyEnrolledStudentIds->isNotEmpty()) {
                    $student = $students->get($alreadyEnrolledStudentIds->first());

                    throw new DuplicateClassOperation(
                        ($student?->name ?? 'A selected student').' is already enrolled in another research class.',
                    );
                }

                $reviewedAt = now();

                foreach ($requests as $joinRequest) {
                    $researchClass = $classes->get($joinRequest->research_class_id);
                    $student = $students->get($joinRequest->student_id);

                    $joinRequest->update([
                        'status' => 'active',
                        'joined_at' => $reviewedAt,
                        'reviewed_by' => $facilitator->getKey(),
                        'reviewed_at' => $reviewedAt,
                    ]);

                    if ($student !== null) {
                        $this->notifications->send(
                            recipient: $student,
                            eventKey: 'class.join-request.approved',
                            title: 'Class join request approved',
                            message: "Your request to join {$researchClass->name} was approved.",
                            category: 'class',
                            routeName: 'student.dashboard',
                            routeParameters: ['tab' => 'classes'],
                            sourceType: ResearchClassEnrollment::class,
                            sourceId: $joinRequest->getKey(),
                            actor: $facilitator,
                            contextLabel: $researchClass->name,
                            actingAs: 'Student Researcher',
                            occurrence: 'active',
                        );
                    }

                    $this->auditLogs->write(
                        actor: $facilitator,
                        event: 'class.join-request.approved',
                        description: 'A research class join request was approved through bulk review.',
                        requestContext: AuditRequestContext::fromRequest(request()),
                        auditable: $joinRequest,
                        subjectName: $student?->name ?? 'Student join request',
                        subjectEmail: $student?->email,
                        oldValues: ['status' => 'pending', 'research_class_id' => $researchClass->getKey()],
                        newValues: ['status' => 'active', 'research_class_id' => $researchClass->getKey()],
                        actorContext: 'research-facilitator',
                    );
                }

                return $requests->each->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The selected join requests could not be approved. Please try again.');
        }
    }
}

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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReviewResearchClassJoinRequest
{
    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function approve(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
    ): ResearchClassEnrollment {
        return $this->review($facilitator, $researchClass, $joinRequest, 'active');
    }

    public function reject(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
    ): ResearchClassEnrollment {
        return $this->review($facilitator, $researchClass, $joinRequest, 'rejected');
    }

    private function review(
        User $facilitator,
        ResearchClass $researchClass,
        ResearchClassEnrollment $joinRequest,
        string $decision,
    ): ResearchClassEnrollment {
        try {
            return DB::transaction(function () use (
                $facilitator,
                $researchClass,
                $joinRequest,
                $decision,
            ): ResearchClassEnrollment {
                $lockedClass = ResearchClass::query()
                    ->whereKey($researchClass->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new ClassOperationException('This join request does not belong to your class.');
                }

                if (! $lockedClass->is_active) {
                    throw new ClassOperationException('This class is no longer available.');
                }

                $lockedRequest = ResearchClassEnrollment::query()
                    ->whereKey($joinRequest->getKey())
                    ->where('research_class_id', $lockedClass->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedRequest === null) {
                    throw new ClassOperationException('The join request was not found for this class.');
                }

                if ($lockedRequest->status !== 'pending') {
                    throw new DuplicateClassOperation('This join request has already been reviewed.');
                }

                if ($decision === 'active') {
                    $alreadyEnrolled = ResearchClassEnrollment::query()
                        ->where('student_id', $lockedRequest->student_id)
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyEnrolled) {
                        throw new DuplicateClassOperation('This student is already enrolled in another research class.');
                    }

                    $activeStudents = ResearchClassEnrollment::query()
                        ->where('research_class_id', $lockedClass->getKey())
                        ->where('status', 'active')
                        ->count();

                    if ($activeStudents >= $lockedClass->max_students) {
                        throw new ClassOperationException('This class has reached its enrollment limit.');
                    }
                }

                $lockedRequest->update([
                    'status' => $decision,
                    'joined_at' => $decision === 'active' ? now() : null,
                    'reviewed_by' => $facilitator->getKey(),
                    'reviewed_at' => now(),
                ]);

                $student = User::query()->find($lockedRequest->student_id);

                if ($student !== null) {
                    $decisionLabel = $decision === 'active' ? 'approved' : 'rejected';
                    $this->notifications->send(
                        recipient: $student,
                        eventKey: "class.join-request.{$decisionLabel}",
                        title: "Class join request {$decisionLabel}",
                        message: "Your request to join {$lockedClass->name} was {$decisionLabel}.",
                        category: 'class',
                        routeName: 'student.dashboard',
                        routeParameters: ['tab' => 'classes'],
                        sourceType: ResearchClassEnrollment::class,
                        sourceId: $lockedRequest->getKey(),
                        actor: $facilitator,
                        contextLabel: $lockedClass->name,
                        actingAs: 'Student Researcher',
                        occurrence: $decision,
                    );
                }

                $this->auditLogs->write(
                    actor: $facilitator,
                    event: $decision === 'active' ? 'class.join-request.approved' : 'class.join-request.rejected',
                    description: 'A research class join request was reviewed.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $lockedRequest,
                    subjectName: $student?->name ?? 'Student join request',
                    subjectEmail: $student?->email,
                    oldValues: ['status' => 'pending', 'research_class_id' => $lockedClass->getKey()],
                    newValues: ['status' => $decision, 'research_class_id' => $lockedClass->getKey()],
                    actorContext: 'research-facilitator',
                );

                return $lockedRequest->refresh();
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The join request could not be reviewed. Please try again.');
        }
    }
}

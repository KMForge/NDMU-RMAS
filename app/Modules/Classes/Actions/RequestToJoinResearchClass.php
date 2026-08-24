<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\Classes\Exceptions\ClassJoinRateLimited;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class RequestToJoinResearchClass
{
    private const FAILED_ATTEMPTS_LIMIT = 5;

    private const FAILED_ATTEMPTS_DECAY_SECONDS = 600;

    private const MAX_REJECTED_ATTEMPTS_PER_CLASS = 5;

    public function __construct(
        private readonly WorkflowNotificationDispatcher $notifications,
    ) {}

    public function handle(User $student, string $joinCode): ResearchClassEnrollment
    {
        try {
            return DB::transaction(function () use ($student, $joinCode): ResearchClassEnrollment {
                $failedAttemptKey = $this->failedAttemptKey($student);

                if (RateLimiter::tooManyAttempts($failedAttemptKey, self::FAILED_ATTEMPTS_LIMIT)) {
                    throw new ClassJoinRateLimited(
                        'Too many failed class-code attempts. Please wait before trying again.',
                        RateLimiter::availableIn($failedAttemptKey),
                    );
                }

                $researchClass = ResearchClass::query()
                    ->where('join_code_hash', ResearchClass::joinCodeFingerprint($joinCode))
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if ($researchClass === null) {
                    RateLimiter::hit($failedAttemptKey, self::FAILED_ATTEMPTS_DECAY_SECONDS);

                    throw new ClassOperationException('No active class was found for that join code.');
                }

                RateLimiter::clear($failedAttemptKey);

                $activeEnrollment = ResearchClassEnrollment::query()
                    ->where('student_id', $student->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($activeEnrollment !== null) {
                    throw new DuplicateClassOperation('You are already enrolled in a research class.');
                }

                $pendingEnrollment = ResearchClassEnrollment::query()
                    ->where('student_id', $student->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($pendingEnrollment !== null) {
                    throw new DuplicateClassOperation('Your join request is already pending facilitator review.');
                }

                $this->enforceRejectionPolicy($student, $researchClass);

                $activeStudents = ResearchClassEnrollment::query()
                    ->where('research_class_id', $researchClass->getKey())
                    ->where('status', 'active')
                    ->count();

                if ($activeStudents >= $researchClass->max_students) {
                    throw new ClassOperationException('This class has reached its enrollment limit.');
                }

                $requestData = [
                    'status' => 'pending',
                    'requested_at' => now(),
                    'joined_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ];

                $enrollment = ResearchClassEnrollment::query()->create([
                    'research_class_id' => $researchClass->getKey(),
                    'student_id' => $student->getKey(),
                    ...$requestData,
                ]);

                $facilitator = User::query()->find($researchClass->facilitator_id);

                if ($facilitator !== null) {
                    $this->notifications->send(
                        recipient: $facilitator,
                        eventKey: 'class.join-request.submitted',
                        title: 'New class join request',
                        message: "{$student->name} requested to join {$researchClass->name}.",
                        category: 'class',
                        routeName: 'facilitator.dashboard',
                        routeParameters: ['tab' => 'join-requests'],
                        sourceType: ResearchClassEnrollment::class,
                        sourceId: $enrollment->getKey(),
                        actor: $student,
                        contextLabel: $researchClass->name,
                        actingAs: 'Research Facilitator',
                    );
                }

                return $enrollment;
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The join request could not be submitted. Please try again.');
        }
    }

    private function enforceRejectionPolicy(User $student, ResearchClass $researchClass): void
    {
        $rejectedRequests = ResearchClassEnrollment::query()
            ->where('student_id', $student->getKey())
            ->where('research_class_id', $researchClass->getKey())
            ->where('status', 'rejected')
            ->orderByDesc('reviewed_at')
            ->lockForUpdate()
            ->get();

        $rejectedCount = $rejectedRequests->count();

        if ($rejectedCount >= self::MAX_REJECTED_ATTEMPTS_PER_CLASS) {
            throw new DuplicateClassOperation(
                'You have reached the maximum number of rejected attempts for this class. Please contact the facilitator or administrator.',
            );
        }

        $lastRejectedAt = $rejectedRequests->first()?->reviewed_at;

        if ($lastRejectedAt === null) {
            return;
        }

        $cooldownHours = match (true) {
            $rejectedCount <= 1 => 24,
            $rejectedCount === 2 => 48,
            default => 72,
        };

        $availableAt = $lastRejectedAt->addHours($cooldownHours);

        if ($availableAt->isFuture()) {
            throw new DuplicateClassOperation(
                'You can request this class again '.$availableAt->diffForHumans().'.',
            );
        }
    }

    private function failedAttemptKey(User $student): string
    {
        return 'class-join-failed|'.$student->getKey();
    }
}

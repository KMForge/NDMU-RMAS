<?php

namespace Tests\Feature\Classes;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResearchClassJoinRequestsPhase11Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        RateLimiter::clear('class-join-failed|1');
    }

    public function test_student_join_code_creates_pending_request_not_active_enrollment(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $researchClass = $this->createClass($facilitator, 'PHASE1101', 'Phase 11 Class');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'phase-1101'])
            ->assertCreated()
            ->assertJsonPath('message', 'Your join request was submitted for facilitator review.')
            ->assertJsonPath('join_request.status', 'pending')
            ->assertJsonPath('join_request.class_name', 'Phase 11 Class')
            ->assertJsonMissingPath('join_request.join_code');

        $this->assertDatabaseHas('research_class_enrollments', [
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'pending',
            'joined_at' => null,
        ]);

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'join-requests']))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Phase 11 Class')
            ->assertViewHas('classRequestStats', [
                'pending' => 1,
                'approved' => 0,
                'rejected' => 0,
                'total' => 1,
            ]);
    }

    public function test_student_with_active_class_cannot_request_another_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $activeClass = $this->createClass($facilitator, 'ACTIVE01');
        $targetClass = $this->createClass($facilitator, 'TARGET01');
        $this->enroll($activeClass, $student, 'active');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $targetClass->revealJoinCode()])
            ->assertConflict()
            ->assertExactJson(['message' => 'You are already enrolled in a research class.']);

        $this->assertDatabaseCount('research_class_enrollments', 1);
    }

    public function test_student_can_have_only_one_pending_request_across_all_classes(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $firstClass = $this->createClass($facilitator, 'PEND0001');
        $secondClass = $this->createClass($facilitator, 'PEND0002');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $firstClass->revealJoinCode()])
            ->assertCreated();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $secondClass->revealJoinCode()])
            ->assertConflict()
            ->assertExactJson(['message' => 'Your join request is already pending facilitator review.']);

        $this->assertDatabaseCount('research_class_enrollments', 1);
    }

    public function test_rejected_history_is_preserved_and_cooldown_is_enforced(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $researchClass = $this->createClass($facilitator, 'RETRY001');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $researchClass->revealJoinCode()])
            ->assertCreated();

        $firstRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.reject', [$researchClass, $firstRequest]))
            ->assertOk()
            ->assertJsonPath('join_request.status', 'rejected');

        $this->travel(23)->hours();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $researchClass->revealJoinCode()])
            ->assertConflict();

        $this->travel(2)->hours();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $researchClass->revealJoinCode()])
            ->assertCreated()
            ->assertJsonPath('join_request.status', 'pending');

        $this->assertDatabaseCount('research_class_enrollments', 2);
        $this->assertDatabaseHas('research_class_enrollments', [
            'id' => $firstRequest->getKey(),
            'status' => 'rejected',
        ]);
    }

    public function test_student_is_blocked_after_five_rejected_attempts_for_same_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $researchClass = $this->createClass($facilitator, 'FIVE0001');

        foreach (range(1, 5) as $attempt) {
            $this->enroll(
                $researchClass,
                $student,
                'rejected',
                requestedAt: now()->subDays(10 + $attempt),
                reviewedAt: now()->subDays(10),
                reviewedBy: $facilitator,
            );
        }

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => $researchClass->revealJoinCode()])
            ->assertConflict()
            ->assertExactJson([
                'message' => 'You have reached the maximum number of rejected attempts for this class. Please contact the facilitator or administrator.',
            ]);
    }

    public function test_failed_join_code_attempts_are_rate_limited(): void
    {
        $student = $this->userWithRole('student');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($student)
                ->postJson(route('student.classes.join'), ['join_code' => 'BADCODE'.$attempt])
                ->assertUnprocessable()
                ->assertExactJson(['message' => 'No active class was found for that join code.']);
        }

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'BADCODE6'])
            ->assertTooManyRequests()
            ->assertExactJson(['message' => 'Too many failed class-code attempts. Please wait before trying again.']);
    }

    public function test_approval_rechecks_student_has_no_other_active_class_inside_transaction(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $requestedClass = $this->createClass($facilitator, 'REQ00001');
        $otherClass = $this->createClass($facilitator, 'OTH00001');
        $pendingRequest = $this->enroll($requestedClass, $student, 'pending');
        $this->enroll($otherClass, $student, 'active');

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [$requestedClass, $pendingRequest]))
            ->assertConflict()
            ->assertExactJson(['message' => 'This student is already enrolled in another research class.']);

        $this->assertSame('pending', $pendingRequest->fresh()->status);
    }

    public function test_only_owning_facilitator_can_review_join_request(): void
    {
        $owner = $this->userWithRole('research-facilitator');
        $otherFacilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $researchClass = $this->createClass($owner, 'OWN00001');
        $pendingRequest = $this->enroll($researchClass, $student, 'pending');

        $this->actingAs($otherFacilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [$researchClass, $pendingRequest]))
            ->assertForbidden();

        $this->assertSame('pending', $pendingRequest->fresh()->status);
    }

    public function test_approval_makes_student_active_member_of_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student');
        $researchClass = $this->createClass($facilitator, 'APP00001');
        $pendingRequest = $this->enroll($researchClass, $student, 'pending');

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [$researchClass, $pendingRequest]))
            ->assertOk()
            ->assertJsonPath('message', 'Join request approved. The student is now enrolled.')
            ->assertJsonPath('join_request.status', 'active');

        $pendingRequest->refresh();

        $this->assertSame('active', $pendingRequest->status);
        $this->assertNotNull($pendingRequest->joined_at);
        $this->assertSame($facilitator->getKey(), $pendingRequest->reviewed_by);

        $this->actingAs($student)
            ->get(route('student.classes.show', $researchClass))
            ->assertOk()
            ->assertSee($researchClass->name)
            ->assertSee('Enrolled');
    }

    public function test_facilitator_can_bulk_approve_selected_pending_join_requests(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator, 'BULK0001', 'Bulk Approval Class');
        $students = collect(range(1, 3))->map(fn () => $this->userWithRole('student'));
        $requests = $students->map(fn (User $student) => $this->enroll($researchClass, $student, 'pending'));
        $requestIds = $requests->pluck('id')->all();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.bulk-approve'), [
                'join_request_ids' => $requestIds,
            ])
            ->assertOk()
            ->assertJsonPath('approved_count', 3)
            ->assertJsonPath('join_request_ids', $requestIds);

        $this->assertSame(
            ['active'],
            ResearchClassEnrollment::query()->whereKey($requestIds)->pluck('status')->unique()->values()->all(),
        );
        $this->assertSame(3, $students->sum(fn (User $student): int => $student->notifications()->where('data->event_key', 'class.join-request.approved')->count()));
        $this->assertDatabaseCount('audit_logs', 3);
    }

    public function test_bulk_approval_is_atomic_when_class_capacity_is_insufficient(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator, 'BULK0002', 'Nearly Full Class');
        $researchClass->update(['max_students' => 2]);
        $this->enroll($researchClass, $this->userWithRole('student'), 'active');
        $requests = collect(range(1, 2))->map(
            fn () => $this->enroll($researchClass, $this->userWithRole('student'), 'pending'),
        );
        $requestIds = $requests->pluck('id')->all();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.bulk-approve'), [
                'join_request_ids' => $requestIds,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Nearly Full Class only has 1 available seat. Reduce the selection and try again.');

        $this->assertSame(
            ['pending'],
            ResearchClassEnrollment::query()->whereKey($requestIds)->pluck('status')->unique()->values()->all(),
        );
    }

    public function test_bulk_approval_rejects_foreign_facilitator_requests_without_partial_updates(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $otherFacilitator = $this->userWithRole('research-facilitator');
        $ownedClass = $this->createClass($facilitator, 'BULK0003', 'Owned Class');
        $foreignClass = $this->createClass($otherFacilitator, 'BULK0004', 'Foreign Class');
        $ownedRequest = $this->enroll($ownedClass, $this->userWithRole('student'), 'pending');
        $foreignRequest = $this->enroll($foreignClass, $this->userWithRole('student'), 'pending');

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.bulk-approve'), [
                'join_request_ids' => [$ownedRequest->id, $foreignRequest->id],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'One or more selected join requests do not belong to your classes.');

        $this->assertSame('pending', $ownedRequest->fresh()->status);
        $this->assertSame('pending', $foreignRequest->fresh()->status);
    }

    public function test_bulk_approval_requires_at_least_one_distinct_request(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.bulk-approve'), [
                'join_request_ids' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('join_request_ids');
    }

    public function test_join_request_page_displays_bulk_selection_controls(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator, 'BULK0005');
        $joinRequest = $this->enroll($researchClass, $this->userWithRole('student'), 'pending');

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'join-requests']))
            ->assertOk()
            ->assertSee('Select all pending requests shown (1)')
            ->assertSee('Approve Selected')
            ->assertSee('join_request_ids[]', false)
            ->assertSee((string) $joinRequest->id);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createClass(User $facilitator, string $joinCode, string $name = 'Capstone Class'): ResearchClass
    {
        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => $name,
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode($joinCode);
        $researchClass->save();

        return $researchClass;
    }

    private function enroll(
        ResearchClass $researchClass,
        User $student,
        string $status,
        mixed $requestedAt = null,
        mixed $reviewedAt = null,
        ?User $reviewedBy = null,
    ): ResearchClassEnrollment {
        return ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => $status,
            'requested_at' => $requestedAt ?? now()->subHour(),
            'joined_at' => $status === 'active' ? now() : null,
            'reviewed_at' => $reviewedAt,
            'reviewed_by' => $reviewedBy?->getKey(),
        ]);
    }
}

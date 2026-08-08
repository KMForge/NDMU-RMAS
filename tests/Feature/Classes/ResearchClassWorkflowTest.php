<?php

namespace Tests\Feature\Classes;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResearchClassWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Superseded by Phase 10 and Phase 11 focused class workflow tests during the backend rebuild.');

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_facilitator_class_code_is_always_generated_and_encrypted(): void
    {
        $facilitator = $this->facilitator();

        $response = $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Secure Research Class <script>alert(1)</script>',
                'description' => 'Research students only.',
                'join_code' => 'CLIENT-CANNOT-CHOOSE',
                'max_students' => 25,
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Class created successfully.')
            ->assertJsonPath('class.name', 'Secure Research Class alert(1)')
            ->assertJsonPath('class.max_students', 25);

        $researchClass = ResearchClass::query()->sole();
        $generatedCode = $response->json('class.join_code');

        $this->assertSame($facilitator->getKey(), $researchClass->facilitator_id);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $generatedCode);
        $this->assertNotSame('CLIENTCANNOTCHOOSE', $generatedCode);
        $this->assertSame($generatedCode, $researchClass->revealJoinCode());
        $this->assertStringNotContainsString($generatedCode, $researchClass->join_code_encrypted);
        $this->assertNotSame($generatedCode, $researchClass->join_code_hash);
    }

    public function test_blank_join_code_is_generated_securely(): void
    {
        $facilitator = $this->facilitator();

        $response = $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Generated Code Class',
                'description' => null,
                'join_code' => null,
                'max_students' => 50,
            ])
            ->assertCreated();

        $joinCode = $response->json('class.join_code');

        $this->assertIsString($joinCode);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $joinCode);
    }

    public function test_facilitator_dashboard_form_creates_a_class_and_returns_to_classes_tab(): void
    {
        $facilitator = $this->facilitator();

        $response = $this->actingAs($facilitator)
            ->post(route('facilitator.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'CAPSTONE II',
                'description' => 'Capstone class managed by the research facilitator.',
                'max_students' => 50,
            ]);

        $response
            ->assertRedirect(route('facilitator.dashboard', ['tab' => 'classes']))
            ->assertSessionHas('class_success');

        $this->assertDatabaseHas('research_classes', [
            'facilitator_id' => $facilitator->getKey(),
            'name' => 'CAPSTONE II',
        ]);
    }

    public function test_creation_token_prevents_duplicate_class_creation(): void
    {
        $facilitator = $this->facilitator();
        $payload = [
            'creation_token' => (string) Str::uuid(),
            'name' => 'Idempotent Research Class',
            'max_students' => 50,
        ];

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), $payload)
            ->assertCreated();

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), $payload)
            ->assertConflict()
            ->assertExactJson(['message' => 'This class has already been created.']);

        $this->assertDatabaseCount('research_classes', 1);
    }

    public function test_student_can_submit_join_request_using_normalized_code(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $researchClass = $this->createClass($facilitator, 'JOIN-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'join-123'])
            ->assertCreated()
            ->assertJsonPath('message', 'Your join request was submitted for facilitator review.')
            ->assertJsonPath('join_request.status', 'pending')
            ->assertJsonPath('join_request.class_name', $researchClass->name)
            ->assertJsonPath('join_request.facilitator_name', $facilitator->name)
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
            ->assertViewHas('classJoinRequests', fn ($requests): bool => $requests->count() === 1
                && $requests->first()->student_id === $student->getKey()
                && $requests->first()->research_class_id === $researchClass->getKey());
    }

    public function test_join_request_dashboard_has_scoped_totals_search_and_status_filters(): void
    {
        $facilitator = $this->facilitator();
        $otherFacilitator = $this->facilitator();
        $researchClass = $this->createClass($facilitator, 'FILT-123', name: 'Capstone Alpha');
        $otherClass = $this->createClass($otherFacilitator, 'OTHR-456', name: 'Hidden Class');
        $pendingStudent = $this->student();
        $pendingStudent->update(['name' => 'Pending Searchable Student']);
        $approvedStudent = $this->student();
        $rejectedStudent = $this->student();
        $rejectedStudent->update(['student_id' => 'FILTER-2026']);
        $hiddenStudent = $this->student();

        ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $pendingStudent->getKey(),
            'status' => 'pending',
            'requested_at' => now()->subMinutes(3),
        ]);
        ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $approvedStudent->getKey(),
            'status' => 'active',
            'requested_at' => now()->subDays(2),
            'joined_at' => now()->subDay(),
            'reviewed_by' => $facilitator->getKey(),
            'reviewed_at' => now()->subDay(),
        ]);
        ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $rejectedStudent->getKey(),
            'status' => 'rejected',
            'requested_at' => now()->subDays(2),
            'reviewed_by' => $facilitator->getKey(),
            'reviewed_at' => now()->subDay(),
        ]);
        ResearchClassEnrollment::query()->create([
            'research_class_id' => $otherClass->getKey(),
            'student_id' => $hiddenStudent->getKey(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', [
                'tab' => 'join-requests',
                'request_status' => 'all',
            ]))
            ->assertOk()
            ->assertViewHas('requestStats', [
                'pending' => 1,
                'approved' => 1,
                'rejected' => 1,
                'total' => 3,
            ])
            ->assertViewHas('classJoinRequests', fn ($requests): bool => $requests->pluck('student_id')->all() === [
                $pendingStudent->getKey(),
                $approvedStudent->getKey(),
                $rejectedStudent->getKey(),
            ]);

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', [
                'tab' => 'join-requests',
                'request_status' => 'rejected',
                'request_q' => 'FILTER-2026',
            ]))
            ->assertOk()
            ->assertViewHas('classJoinRequests', fn ($requests): bool => $requests->count() === 1
                && $requests->first()->student_id === $rejectedStudent->getKey());
    }

    public function test_student_cannot_submit_duplicate_pending_request(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $this->createClass($facilitator, 'DUPL-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'DUPL-123'])
            ->assertCreated();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'DUPL-123'])
            ->assertConflict()
            ->assertExactJson(['message' => 'Your join request is already pending facilitator review.']);

        $this->assertDatabaseCount('research_class_enrollments', 1);
    }

    public function test_invalid_join_code_returns_safe_error(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'NONE-123'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'No active class was found for that join code.']);
    }

    public function test_class_capacity_is_enforced_transactionally(): void
    {
        $facilitator = $this->facilitator();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $this->createClass($facilitator, 'FULL-123', 1);

        $this->actingAs($firstStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'FULL-123'])
            ->assertCreated();

        $firstRequest = ResearchClassEnrollment::query()
            ->where('student_id', $firstStudent->getKey())
            ->sole();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [
                $firstRequest->researchClass,
                $firstRequest,
            ]))
            ->assertOk();

        $this->actingAs($secondStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'FULL-123'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'This class has reached its enrollment limit.']);
    }

    public function test_facilitator_cannot_approve_request_after_last_slot_is_taken(): void
    {
        $facilitator = $this->facilitator();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $researchClass = $this->createClass($facilitator, 'SLOT-123', 1);

        $this->actingAs($firstStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'SLOT-123'])
            ->assertCreated();
        $this->actingAs($secondStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'SLOT-123'])
            ->assertCreated();

        $firstRequest = ResearchClassEnrollment::query()
            ->where('student_id', $firstStudent->getKey())
            ->sole();
        $secondRequest = ResearchClassEnrollment::query()
            ->where('student_id', $secondStudent->getKey())
            ->sole();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [
                $researchClass,
                $firstRequest,
            ]))
            ->assertOk();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [
                $researchClass,
                $secondRequest,
            ]))
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'This class has reached its enrollment limit.']);

        $this->assertSame('pending', $secondRequest->fresh()->status);
    }

    public function test_owning_facilitator_can_approve_pending_join_request(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $researchClass = $this->createClass($facilitator, 'APRV-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'APRV-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [
                $researchClass,
                $joinRequest,
            ]))
            ->assertOk()
            ->assertJsonPath('join_request.status', 'active');

        $joinRequest->refresh();

        $this->assertSame('active', $joinRequest->status);
        $this->assertNotNull($joinRequest->joined_at);
        $this->assertNotNull($joinRequest->reviewed_at);
        $this->assertSame($facilitator->getKey(), $joinRequest->reviewed_by);

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee($researchClass->name)
            ->assertViewHas(
                'classJoinRequests',
                fn ($requests): bool => $requests->isEmpty(),
            );
    }

    public function test_owning_facilitator_can_reject_and_student_can_request_again(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $researchClass = $this->createClass($facilitator, 'RJCT-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'RJCT-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.reject', [
                $researchClass,
                $joinRequest,
            ]))
            ->assertOk()
            ->assertJsonPath('join_request.status', 'rejected');

        $this->travel(25)->hours();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'RJCT-123'])
            ->assertCreated()
            ->assertJsonPath('join_request.status', 'pending');

        $this->assertDatabaseCount('research_class_enrollments', 2);
        $this->assertDatabaseHas('research_class_enrollments', [
            'id' => $joinRequest->getKey(),
            'status' => 'rejected',
            'reviewed_by' => $facilitator->getKey(),
        ]);
    }

    public function test_another_facilitator_cannot_review_join_request(): void
    {
        $owner = $this->facilitator();
        $otherFacilitator = $this->facilitator();
        $student = $this->student();
        $researchClass = $this->createClass($owner, 'OWNR-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'OWNR-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($otherFacilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [
                $researchClass,
                $joinRequest,
            ]))
            ->assertForbidden();

        $this->assertSame('pending', $joinRequest->fresh()->status);
    }

    public function test_facilitator_cannot_review_request_through_a_different_owned_class(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $requestedClass = $this->createClass($facilitator, 'RQST-123');
        $differentClass = $this->createClass($facilitator, 'DIFF-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'RQST-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.join-requests.approve', [
                $differentClass,
                $joinRequest,
            ]))
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'The join request was not found for this class.']);

        $this->assertSame($requestedClass->getKey(), $joinRequest->research_class_id);
        $this->assertSame('pending', $joinRequest->fresh()->status);
    }

    public function test_reviewed_request_cannot_be_processed_twice(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $researchClass = $this->createClass($facilitator, 'ONCE-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'ONCE-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();
        $route = route('facilitator.classes.join-requests.approve', [$researchClass, $joinRequest]);

        $this->actingAs($facilitator)->patchJson($route)->assertOk();
        $this->actingAs($facilitator)
            ->patchJson($route)
            ->assertConflict()
            ->assertExactJson(['message' => 'This join request has already been reviewed.']);
    }

    public function test_facilitator_can_see_and_approve_a_join_request_from_the_sidebar_page(): void
    {
        $facilitator = $this->facilitator();
        $student = $this->student();
        $student->update(['name' => 'Pending Sidebar Student']);
        $researchClass = $this->createClass($facilitator, 'SIDE-123', name: 'Sidebar Capstone Class');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'SIDE-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'join-requests']))
            ->assertOk()
            ->assertSee('Pending Sidebar Student')
            ->assertSee('Sidebar Capstone Class')
            ->assertSee(route('facilitator.classes.join-requests.approve', [$researchClass, $joinRequest]));

        $this->actingAs($facilitator)
            ->patch(route('facilitator.classes.join-requests.approve', [$researchClass, $joinRequest]))
            ->assertRedirect(route('facilitator.dashboard', ['tab' => 'join-requests']))
            ->assertSessionHas('join_request_success');

        $this->assertDatabaseHas('research_class_enrollments', [
            'id' => $joinRequest->getKey(),
            'status' => 'active',
            'reviewed_by' => $facilitator->getKey(),
        ]);
    }

    public function test_class_permissions_are_enforced(): void
    {
        $facilitator = $this->facilitator();
        Role::findByName('research-facilitator')->syncPermissions(['research.view-assigned']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Forbidden Class',
                'max_students' => 20,
            ])
            ->assertForbidden();
    }

    public function test_dashboards_only_show_classes_in_the_authenticated_users_scope(): void
    {
        $facilitator = $this->facilitator();
        $otherFacilitator = $this->facilitator();
        $student = $this->student();
        $otherStudent = $this->student();

        $ownedClass = $this->createClass($facilitator, 'OWN-123', name: 'Owned Facilitator Class');
        $this->createClass($otherFacilitator, 'OTHR-123', name: 'Other Facilitator Private Class');

        DB::table('research_class_enrollments')->insert([
            'research_class_id' => $ownedClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
            'requested_at' => now(),
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertViewHas('researchClasses', fn ($classes): bool => $classes->count() === 1
                && $classes->first()->name === 'Owned Facilitator Class');

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertViewHas('classes', fn ($classes): bool => $classes->count() === 1
                && $classes->first()->name === 'Owned Facilitator Class');

        $this->actingAs($otherStudent)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertViewHas('classes', fn ($classes): bool => $classes->isEmpty());
    }

    public function test_facilitator_can_open_owned_class_and_view_student_roster(): void
    {
        $facilitator = $this->facilitator();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $researchClass = $this->createClass(
            $facilitator,
            'ROST-123',
            name: 'Secure Roster Class',
        );
        $this->enroll($researchClass, $firstStudent);
        $this->enroll($researchClass, $secondStudent);
        $pendingStudent = $this->student();
        ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $pendingStudent->getKey(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($facilitator)
            ->getJson(route('facilitator.classes.show', $researchClass))
            ->assertOk()
            ->assertJsonPath('class.name', 'Secure Roster Class')
            ->assertJsonPath('class.facilitator.id', $facilitator->getKey())
            ->assertJsonPath('class.join_code', $researchClass->revealJoinCode())
            ->assertJsonCount(2, 'class.students')
            ->assertJsonFragment(['name' => $firstStudent->name])
            ->assertJsonFragment(['name' => $secondStudent->name])
            ->assertJsonMissing(['email' => $pendingStudent->email]);
    }

    public function test_facilitator_cannot_open_another_facilitators_class(): void
    {
        $owner = $this->facilitator();
        $otherFacilitator = $this->facilitator();
        $researchClass = $this->createClass(
            $owner,
            'PRIV-123',
            name: 'Private Facilitator Roster',
        );

        $this->actingAs($otherFacilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertForbidden()
            ->assertDontSee('Private Facilitator Roster');
    }

    public function test_facilitator_can_search_owned_class_roster(): void
    {
        $facilitator = $this->facilitator();
        $matchingStudent = $this->student();
        $matchingStudent->update(['name' => 'Unique Search Student']);
        $otherStudent = $this->student();
        $otherStudent->update(['name' => 'Unrelated Student']);
        $researchClass = $this->createClass($facilitator, 'SRCH-123');
        $this->enroll($researchClass, $matchingStudent);
        $this->enroll($researchClass, $otherStudent);

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', [
                'researchClass' => $researchClass,
                'q' => 'Unique Search',
            ]))
            ->assertOk()
            ->assertSee('Unique Search Student')
            ->assertDontSee('Unrelated Student');
    }

    private function facilitator(): User
    {
        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');

        return $facilitator;
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        return $student;
    }

    private function createClass(
        User $facilitator,
        string $joinCode,
        int $maxStudents = 50,
        string $name = 'Research Class',
    ): ResearchClass {
        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => $name,
            'max_students' => $maxStudents,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode($joinCode);
        $researchClass->save();

        return $researchClass;
    }

    private function enroll(ResearchClass $researchClass, User $student): void
    {
        DB::table('research_class_enrollments')->insert([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
            'requested_at' => now(),
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

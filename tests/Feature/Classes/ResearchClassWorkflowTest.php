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

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_adviser_class_code_is_always_generated_and_encrypted(): void
    {
        $adviser = $this->adviser();

        $response = $this->actingAs($adviser)
            ->postJson(route('adviser.classes.store'), [
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

        $this->assertSame($adviser->getKey(), $researchClass->adviser_id);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $generatedCode);
        $this->assertNotSame('CLIENTCANNOTCHOOSE', $generatedCode);
        $this->assertSame($generatedCode, $researchClass->revealJoinCode());
        $this->assertStringNotContainsString($generatedCode, $researchClass->join_code_encrypted);
        $this->assertNotSame($generatedCode, $researchClass->join_code_hash);
    }

    public function test_blank_join_code_is_generated_securely(): void
    {
        $adviser = $this->adviser();

        $response = $this->actingAs($adviser)
            ->postJson(route('adviser.classes.store'), [
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

    public function test_creation_token_prevents_duplicate_class_creation(): void
    {
        $adviser = $this->adviser();
        $payload = [
            'creation_token' => (string) Str::uuid(),
            'name' => 'Idempotent Research Class',
            'max_students' => 50,
        ];

        $this->actingAs($adviser)
            ->postJson(route('adviser.classes.store'), $payload)
            ->assertCreated();

        $this->actingAs($adviser)
            ->postJson(route('adviser.classes.store'), $payload)
            ->assertConflict()
            ->assertExactJson(['message' => 'This class has already been created.']);

        $this->assertDatabaseCount('research_classes', 1);
    }

    public function test_student_can_submit_join_request_using_normalized_code(): void
    {
        $adviser = $this->adviser();
        $student = $this->student();
        $researchClass = $this->createClass($adviser, 'JOIN-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'join-123'])
            ->assertCreated()
            ->assertJsonPath('message', 'Your join request was submitted for adviser review.')
            ->assertJsonPath('join_request.status', 'pending')
            ->assertJsonPath('join_request.class_name', $researchClass->name)
            ->assertJsonPath('join_request.adviser_name', $adviser->name)
            ->assertJsonMissingPath('join_request.join_code');

        $this->assertDatabaseHas('research_class_enrollments', [
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'pending',
            'joined_at' => null,
        ]);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'requests']))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($researchClass->name);
    }

    public function test_join_request_dashboard_has_scoped_totals_search_and_status_filters(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $researchClass = $this->createClass($adviser, 'FILT-123', name: 'Capstone Alpha');
        $otherClass = $this->createClass($otherAdviser, 'OTHR-456', name: 'Hidden Class');
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
            'reviewed_by' => $adviser->getKey(),
            'reviewed_at' => now()->subDay(),
        ]);
        ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $rejectedStudent->getKey(),
            'status' => 'rejected',
            'requested_at' => now()->subDays(2),
            'reviewed_by' => $adviser->getKey(),
            'reviewed_at' => now()->subDay(),
        ]);
        ResearchClassEnrollment::query()->create([
            'research_class_id' => $otherClass->getKey(),
            'student_id' => $hiddenStudent->getKey(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'requests',
                'request_status' => 'all',
            ]))
            ->assertOk()
            ->assertViewHas('requestStats', [
                'pending' => 1,
                'approved' => 1,
                'rejected' => 1,
                'total' => 3,
            ])
            ->assertSee($pendingStudent->name)
            ->assertSee($approvedStudent->name)
            ->assertSee($rejectedStudent->name)
            ->assertDontSee($hiddenStudent->name);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', [
                'tab' => 'requests',
                'request_status' => 'rejected',
                'request_q' => 'FILTER-2026',
            ]))
            ->assertOk()
            ->assertSee($rejectedStudent->name)
            ->assertDontSee($pendingStudent->name)
            ->assertDontSee($approvedStudent->name);
    }

    public function test_student_cannot_submit_duplicate_pending_request(): void
    {
        $adviser = $this->adviser();
        $student = $this->student();
        $this->createClass($adviser, 'DUPL-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'DUPL-123'])
            ->assertCreated();

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'DUPL-123'])
            ->assertConflict()
            ->assertExactJson(['message' => 'Your join request is already pending adviser review.']);

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
        $adviser = $this->adviser();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $this->createClass($adviser, 'FULL-123', 1);

        $this->actingAs($firstStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'FULL-123'])
            ->assertCreated();

        $firstRequest = ResearchClassEnrollment::query()
            ->where('student_id', $firstStudent->getKey())
            ->sole();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.classes.join-requests.approve', [
                $firstRequest->researchClass,
                $firstRequest,
            ]))
            ->assertOk();

        $this->actingAs($secondStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'FULL-123'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'This class has reached its enrollment limit.']);
    }

    public function test_adviser_cannot_approve_request_after_last_slot_is_taken(): void
    {
        $adviser = $this->adviser();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $researchClass = $this->createClass($adviser, 'SLOT-123', 1);

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

        $this->actingAs($adviser)
            ->patchJson(route('adviser.classes.join-requests.approve', [
                $researchClass,
                $firstRequest,
            ]))
            ->assertOk();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.classes.join-requests.approve', [
                $researchClass,
                $secondRequest,
            ]))
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'This class has reached its enrollment limit.']);

        $this->assertSame('pending', $secondRequest->fresh()->status);
    }

    public function test_owning_adviser_can_approve_pending_join_request(): void
    {
        $adviser = $this->adviser();
        $student = $this->student();
        $researchClass = $this->createClass($adviser, 'APRV-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'APRV-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.classes.join-requests.approve', [
                $researchClass,
                $joinRequest,
            ]))
            ->assertOk()
            ->assertJsonPath('join_request.status', 'active');

        $joinRequest->refresh();

        $this->assertSame('active', $joinRequest->status);
        $this->assertNotNull($joinRequest->joined_at);
        $this->assertNotNull($joinRequest->reviewed_at);
        $this->assertSame($adviser->getKey(), $joinRequest->reviewed_by);

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee($researchClass->name)
            ->assertDontSee('pending');
    }

    public function test_owning_adviser_can_reject_and_student_can_request_again(): void
    {
        $adviser = $this->adviser();
        $student = $this->student();
        $researchClass = $this->createClass($adviser, 'RJCT-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'RJCT-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.classes.join-requests.reject', [
                $researchClass,
                $joinRequest,
            ]))
            ->assertOk()
            ->assertJsonPath('join_request.status', 'rejected');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'RJCT-123'])
            ->assertCreated()
            ->assertJsonPath('join_request.status', 'pending');

        $this->assertDatabaseCount('research_class_enrollments', 1);
        $this->assertDatabaseHas('research_class_enrollments', [
            'id' => $joinRequest->getKey(),
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    public function test_another_adviser_cannot_review_join_request(): void
    {
        $owner = $this->adviser();
        $otherAdviser = $this->adviser();
        $student = $this->student();
        $researchClass = $this->createClass($owner, 'OWNR-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'OWNR-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($otherAdviser)
            ->patchJson(route('adviser.classes.join-requests.approve', [
                $researchClass,
                $joinRequest,
            ]))
            ->assertForbidden();

        $this->assertSame('pending', $joinRequest->fresh()->status);
    }

    public function test_adviser_cannot_review_request_through_a_different_owned_class(): void
    {
        $adviser = $this->adviser();
        $student = $this->student();
        $requestedClass = $this->createClass($adviser, 'RQST-123');
        $differentClass = $this->createClass($adviser, 'DIFF-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'RQST-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.classes.join-requests.approve', [
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
        $adviser = $this->adviser();
        $student = $this->student();
        $researchClass = $this->createClass($adviser, 'ONCE-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'ONCE-123'])
            ->assertCreated();

        $joinRequest = ResearchClassEnrollment::query()->sole();
        $route = route('adviser.classes.join-requests.approve', [$researchClass, $joinRequest]);

        $this->actingAs($adviser)->patchJson($route)->assertOk();
        $this->actingAs($adviser)
            ->patchJson($route)
            ->assertConflict()
            ->assertExactJson(['message' => 'This join request has already been reviewed.']);
    }

    public function test_class_permissions_are_enforced(): void
    {
        $adviser = $this->adviser();
        Role::findByName('research-adviser')->syncPermissions(['research.view-assigned']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($adviser)
            ->postJson(route('adviser.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Forbidden Class',
                'max_students' => 20,
            ])
            ->assertForbidden()
            ->assertExactJson(['message' => 'You do not have permission to create classes.']);
    }

    public function test_dashboards_only_show_classes_in_the_authenticated_users_scope(): void
    {
        $adviser = $this->adviser();
        $otherAdviser = $this->adviser();
        $student = $this->student();
        $otherStudent = $this->student();

        $ownedClass = $this->createClass($adviser, 'OWN-123', name: 'Owned Adviser Class');
        $this->createClass($otherAdviser, 'OTHR-123', name: 'Other Adviser Private Class');

        DB::table('research_class_enrollments')->insert([
            'research_class_id' => $ownedClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
            'requested_at' => now(),
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee('Owned Adviser Class')
            ->assertDontSee('Other Adviser Private Class');

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee('Owned Adviser Class')
            ->assertDontSee('Other Adviser Private Class');

        $this->actingAs($otherStudent)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertDontSee('Owned Adviser Class');
    }

    public function test_adviser_can_open_owned_class_and_view_student_roster(): void
    {
        $adviser = $this->adviser();
        $firstStudent = $this->student();
        $secondStudent = $this->student();
        $researchClass = $this->createClass(
            $adviser,
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

        $this->actingAs($adviser)
            ->get(route('adviser.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Secure Roster Class')
            ->assertSee('Class Adviser')
            ->assertSee($adviser->name)
            ->assertSee($researchClass->revealJoinCode())
            ->assertSee($firstStudent->name)
            ->assertSee($firstStudent->email)
            ->assertSee($secondStudent->name)
            ->assertDontSee($pendingStudent->email)
            ->assertSee('2 / 50');
    }

    public function test_adviser_cannot_open_another_advisers_class(): void
    {
        $owner = $this->adviser();
        $otherAdviser = $this->adviser();
        $researchClass = $this->createClass(
            $owner,
            'PRIV-123',
            name: 'Private Adviser Roster',
        );

        $this->actingAs($otherAdviser)
            ->get(route('adviser.classes.show', $researchClass))
            ->assertForbidden()
            ->assertDontSee('Private Adviser Roster');
    }

    public function test_adviser_can_search_owned_class_roster(): void
    {
        $adviser = $this->adviser();
        $matchingStudent = $this->student();
        $matchingStudent->update(['name' => 'Unique Search Student']);
        $otherStudent = $this->student();
        $otherStudent->update(['name' => 'Unrelated Student']);
        $researchClass = $this->createClass($adviser, 'SRCH-123');
        $this->enroll($researchClass, $matchingStudent);
        $this->enroll($researchClass, $otherStudent);

        $this->actingAs($adviser)
            ->get(route('adviser.classes.show', [
                'researchClass' => $researchClass,
                'q' => 'Unique Search',
            ]))
            ->assertOk()
            ->assertSee('Unique Search Student')
            ->assertDontSee('Unrelated Student');
    }

    private function adviser(): User
    {
        $adviser = User::factory()->create();
        $adviser->assignRole('research-adviser');

        return $adviser;
    }

    private function student(): User
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        return $student;
    }

    private function createClass(
        User $adviser,
        string $joinCode,
        int $maxStudents = 50,
        string $name = 'Research Class',
    ): ResearchClass {
        $researchClass = new ResearchClass([
            'adviser_id' => $adviser->getKey(),
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

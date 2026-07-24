<?php

namespace Tests\Feature\Classes;

use App\Models\ResearchClass;
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

    public function test_student_can_join_active_class_using_normalized_code(): void
    {
        $adviser = $this->adviser();
        $student = $this->student();
        $researchClass = $this->createClass($adviser, 'JOIN-123');

        $this->actingAs($student)
            ->postJson(route('student.classes.join'), ['join_code' => 'join-123'])
            ->assertCreated()
            ->assertJsonPath('message', 'You joined the class successfully.')
            ->assertJsonPath('class.name', $researchClass->name)
            ->assertJsonPath('class.adviser', $adviser->name)
            ->assertJsonMissingPath('class.join_code');

        $this->assertDatabaseHas('research_class_enrollments', [
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => 'active',
        ]);
    }

    public function test_student_cannot_join_same_class_twice(): void
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
            ->assertExactJson(['message' => 'You have already joined this class.']);

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

        $this->actingAs($secondStudent)
            ->postJson(route('student.classes.join'), ['join_code' => 'FULL-123'])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'This class has reached its enrollment limit.']);
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
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

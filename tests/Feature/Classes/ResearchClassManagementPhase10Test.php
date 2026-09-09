<?php

namespace Tests\Feature\Classes;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResearchClassManagementPhase10Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_facilitator_can_create_a_research_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');

        $response = $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'ITCAP 102 - IT4A <script>alert(1)</script>',
                'description' => 'Capstone Project II',
                'max_students' => 50,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Class created successfully.')
            ->assertJsonPath('class.name', 'ITCAP 102 - IT4A alert(1)')
            ->assertJsonPath('class.max_students', 50);

        $researchClass = ResearchClass::query()->sole();

        $this->assertSame($facilitator->getKey(), $researchClass->facilitator_id);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $response->json('class.join_code'));
        $this->assertDatabaseHas('research_classes', [
            'id' => $researchClass->getKey(),
            'facilitator_id' => $facilitator->getKey(),
            'name' => 'ITCAP 102 - IT4A alert(1)',
        ]);
    }

    public function test_facilitator_dashboard_lists_only_owned_research_classes(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $otherFacilitator = $this->userWithRole('research-facilitator');

        $ownedClass = $this->createClass($facilitator, 'Owned Capstone Class');
        $this->createClass($otherFacilitator, 'Hidden Capstone Class');

        $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee('Owned Capstone Class')
            ->assertSee(route('facilitator.classes.show', $ownedClass))
            ->assertDontSee('Hidden Capstone Class');
    }

    public function test_facilitator_can_open_owned_class_details_and_view_active_students(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $activeStudent = $this->userWithRole('student');
        $pendingStudent = $this->userWithRole('student');
        $activeStudent->update([
            'name' => 'Active Class Student',
            'student_id' => 'STU-2026-0001',
            'program' => 'BS Computer Science',
        ]);
        $pendingStudent->update(['name' => 'Pending Class Student']);
        $researchClass = $this->createClass($facilitator, 'Roster Class');
        $this->enroll($researchClass, $activeStudent, 'active');
        $this->enroll($researchClass, $pendingStudent, 'pending');

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Roster Class')
            ->assertSee('Student Roster')
            ->assertSee('Active Class Student')
            ->assertSee('STU-2026-0001')
            ->assertSee('BS Computer Science')
            ->assertSee('Pending Form Approvals')
            ->assertSee('Join Requests')
            ->assertSee('Research Statistics')
            ->assertSee('Research Reports')
            ->assertSee('Research Repository')
            ->assertSee('Official Forms')
            ->assertSee('Notifications')
            ->assertSee('Settings')
            ->assertDontSee('Pending Class Student');
    }

    public function test_facilitator_cannot_open_another_facilitators_class(): void
    {
        $owner = $this->userWithRole('research-facilitator');
        $otherFacilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($owner, 'Private Class');

        $this->actingAs($otherFacilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertForbidden();
    }

    public function test_class_creation_validates_required_fields(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.store'), [
                'creation_token' => 'not-a-uuid',
                'name' => '',
                'max_students' => 101,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['creation_token']);

        $this->assertDatabaseCount('research_classes', 0);
    }

    public function test_user_without_create_permission_cannot_create_class(): void
    {
        $student = $this->userWithRole('student');

        $this->actingAs($student)
            ->postJson(route('facilitator.classes.store'), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Unauthorized Class',
                'max_students' => 50,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('research_classes', 0);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createClass(User $facilitator, string $name): ResearchClass
    {
        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => $name,
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode(Str::upper(Str::random(8)));
        $researchClass->save();

        return $researchClass;
    }

    private function enroll(ResearchClass $researchClass, User $student, string $status): ResearchClassEnrollment
    {
        return ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => $status,
            'requested_at' => now()->subDay(),
            'joined_at' => $status === 'active' ? now() : null,
        ]);
    }
}

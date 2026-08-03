<?php

namespace Tests\Feature\Classes;

use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResearchClassGroupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_facilitator_can_create_a_group_for_an_owned_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator);

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.groups.store', $researchClass), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Capstone Group 1',
            ])
            ->assertCreated()
            ->assertJsonPath('group.name', 'Capstone Group 1');

        $this->assertDatabaseHas('research_class_groups', [
            'research_class_id' => $researchClass->getKey(),
            'name' => 'Capstone Group 1',
            'created_by' => $facilitator->getKey(),
        ]);
    }

    public function test_facilitator_can_assign_only_an_approved_class_student_to_a_group(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');
        $enrollment = $this->enroll($researchClass, $student, 'active');

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.students.assign', [
                $researchClass, $group, $enrollment,
            ]))
            ->assertOk()
            ->assertJsonPath('membership.student_id', $student->getKey());

        $pendingStudent = $this->userWithRole('student-researcher');
        $pendingEnrollment = $this->enroll($researchClass, $pendingStudent, 'pending');

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.students.assign', [
                $researchClass, $group, $pendingEnrollment,
            ]))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('research_class_group_members', [
            'student_id' => $pendingStudent->getKey(),
        ]);
    }

    public function test_moving_a_student_to_another_group_does_not_duplicate_membership(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $firstGroup = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');
        $secondGroup = $this->createGroup($researchClass, $facilitator, 'Capstone Group 2');
        $enrollment = $this->enroll($researchClass, $student, 'active');

        foreach ([$firstGroup, $secondGroup] as $group) {
            $this->actingAs($facilitator)
                ->putJson(route('facilitator.classes.groups.students.assign', [
                    $researchClass, $group, $enrollment,
                ]))
                ->assertOk();
        }

        $this->assertSame(1, ResearchClassGroupMember::query()->count());
        $this->assertDatabaseHas('research_class_group_members', [
            'research_class_group_id' => $secondGroup->getKey(),
            'student_id' => $student->getKey(),
        ]);
    }

    public function test_facilitator_can_assign_an_active_research_adviser_to_a_group(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $adviser = $this->userWithRole('research-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.adviser.assign', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('group.adviser.id', $adviser->getKey());

        $this->assertDatabaseHas('research_class_groups', [
            'id' => $group->getKey(),
            'adviser_id' => $adviser->getKey(),
        ]);
    }

    public function test_facilitator_cannot_manage_another_facilitators_class_groups(): void
    {
        $owner = $this->userWithRole('research-facilitator');
        $otherFacilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($owner);

        $this->actingAs($otherFacilitator)
            ->postJson(route('facilitator.classes.groups.store', $researchClass), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Unauthorized Group',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('research_class_groups', ['name' => 'Unauthorized Group']);
    }

    public function test_adviser_can_view_only_students_in_groups_assigned_to_them(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $adviser = $this->userWithRole('research-adviser');
        $otherAdviser = $this->userWithRole('research-adviser');
        $assignedStudent = $this->userWithRole('student-researcher');
        $hiddenStudent = $this->userWithRole('student-researcher');
        $assignedStudent->update(['name' => 'Assigned Group Student']);
        $hiddenStudent->update(['name' => 'Other Group Student']);
        $researchClass = $this->createClass($facilitator);
        $assignedGroup = $this->createGroup($researchClass, $facilitator, 'Assigned Group');
        $assignedGroup->update(['adviser_id' => $adviser->getKey()]);
        $otherGroup = $this->createGroup($researchClass, $facilitator, 'Other Group');
        $otherGroup->update(['adviser_id' => $otherAdviser->getKey()]);
        $assignedEnrollment = $this->enroll($researchClass, $assignedStudent, 'active');
        $hiddenEnrollment = $this->enroll($researchClass, $hiddenStudent, 'active');

        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $assignedGroup->getKey(),
            'research_class_id' => $researchClass->getKey(),
            'research_class_enrollment_id' => $assignedEnrollment->getKey(),
            'student_id' => $assignedStudent->getKey(),
            'assigned_by' => $facilitator->getKey(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $otherGroup->getKey(),
            'research_class_id' => $researchClass->getKey(),
            'research_class_enrollment_id' => $hiddenEnrollment->getKey(),
            'student_id' => $hiddenStudent->getKey(),
            'assigned_by' => $facilitator->getKey(),
        ]);

        $this->actingAs($adviser)
            ->get(route('adviser.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Assigned Group Student')
            ->assertDontSee('Other Group Student');
    }

    public function test_unassigned_adviser_cannot_view_a_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $unassignedAdviser = $this->userWithRole('research-adviser');
        $researchClass = $this->createClass($facilitator);

        $this->actingAs($unassignedAdviser)
            ->get(route('adviser.classes.show', $researchClass))
            ->assertForbidden();
    }

    public function test_adviser_dashboard_does_not_expose_facilitator_join_request_controls(): void
    {
        $adviser = $this->userWithRole('research-adviser');

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard'))
            ->assertOk()
            ->assertDontSee('Join Requests')
            ->assertDontSee('classes/join-requests');

        $this->assertFalse(Route::has('adviser.classes.join-requests.approve'));
        $this->assertFalse(Route::has('adviser.classes.join-requests.reject'));
    }

    public function test_facilitator_browser_flow_renders_cards_roster_groups_and_adviser_assignment(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student-researcher');
        $adviser = $this->userWithRole('research-adviser');
        $student->update(['name' => 'Browser Flow Student']);
        $adviser->update(['name' => 'Browser Flow Adviser']);
        $researchClass = $this->createClass($facilitator);
        $enrollment = $this->enroll($researchClass, $student, 'active');

        $this->actingAs($facilitator)
            ->post(route('facilitator.classes.groups.store', $researchClass), [
                'creation_token' => (string) Str::uuid(),
                'name' => 'Browser Flow Group',
            ])
            ->assertRedirect(route('facilitator.classes.show', $researchClass));

        $group = ResearchClassGroup::query()->sole();

        $this->actingAs($facilitator)
            ->put(route('facilitator.classes.groups.students.assign', [$researchClass, $group, $enrollment]))
            ->assertRedirect(route('facilitator.classes.show', $researchClass));

        $this->actingAs($facilitator)
            ->put(route('facilitator.classes.groups.adviser.assign', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertRedirect(route('facilitator.classes.show', $researchClass));

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Student Roster')
            ->assertSee('Browser Flow Student')
            ->assertSee('Browser Flow Group')
            ->assertSee('Browser Flow Adviser');
    }

    public function test_student_can_click_an_enrolled_class_and_see_only_their_group(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $adviser = $this->userWithRole('research-adviser');
        $otherAdviser = $this->userWithRole('research-adviser');
        $student = $this->userWithRole('student-researcher');
        $groupMate = $this->userWithRole('student-researcher');
        $hiddenStudent = $this->userWithRole('student-researcher');
        $adviser->update(['name' => 'Student Group Adviser']);
        $groupMate->update(['name' => 'Visible Group Mate']);
        $hiddenStudent->update(['name' => 'Hidden Other Group Student']);
        $researchClass = $this->createClass($facilitator);
        $studentEnrollment = $this->enroll($researchClass, $student, 'active');
        $mateEnrollment = $this->enroll($researchClass, $groupMate, 'active');
        $hiddenEnrollment = $this->enroll($researchClass, $hiddenStudent, 'active');
        $group = $this->createGroup($researchClass, $facilitator, 'Student Capstone Group');
        $group->update(['adviser_id' => $adviser->getKey()]);
        $hiddenGroup = $this->createGroup($researchClass, $facilitator, 'Hidden Capstone Group');
        $hiddenGroup->update(['adviser_id' => $otherAdviser->getKey()]);

        foreach ([[$studentEnrollment, $student], [$mateEnrollment, $groupMate]] as [$enrollment, $member]) {
            ResearchClassGroupMember::query()->create([
                'research_class_group_id' => $group->getKey(),
                'research_class_id' => $researchClass->getKey(),
                'research_class_enrollment_id' => $enrollment->getKey(),
                'student_id' => $member->getKey(),
                'assigned_by' => $facilitator->getKey(),
            ]);
        }
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $hiddenGroup->getKey(),
            'research_class_id' => $researchClass->getKey(),
            'research_class_enrollment_id' => $hiddenEnrollment->getKey(),
            'student_id' => $hiddenStudent->getKey(),
            'assigned_by' => $facilitator->getKey(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee(route('student.classes.show', $researchClass));

        $this->actingAs($student)
            ->get(route('student.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Student Capstone Group')
            ->assertSee('Student Group Adviser')
            ->assertSee('Visible Group Mate')
            ->assertDontSee('Hidden Capstone Group')
            ->assertDontSee('Hidden Other Group Student');
    }

    public function test_student_cannot_open_a_class_without_active_enrollment(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $student = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $this->enroll($researchClass, $student, 'pending');

        $this->actingAs($student)
            ->get(route('student.classes.show', $researchClass))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createClass(User $facilitator): ResearchClass
    {
        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone II',
            'max_students' => 50,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode('CAPS-2026');
        $researchClass->save();

        return $researchClass;
    }

    private function createGroup(ResearchClass $researchClass, User $facilitator, string $name): ResearchClassGroup
    {
        return ResearchClassGroup::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'creation_token' => (string) Str::uuid(),
            'name' => $name,
            'created_by' => $facilitator->getKey(),
        ]);
    }

    private function enroll(ResearchClass $researchClass, User $student, string $status): ResearchClassEnrollment
    {
        return ResearchClassEnrollment::query()->create([
            'research_class_id' => $researchClass->getKey(),
            'student_id' => $student->getKey(),
            'status' => $status,
            'requested_at' => now(),
            'joined_at' => $status === 'active' ? now() : null,
        ]);
    }
}

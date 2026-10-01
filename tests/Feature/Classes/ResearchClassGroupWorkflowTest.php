<?php

namespace Tests\Feature\Classes;

use App\Enums\UserType;
use App\Models\MilestoneDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchClassGroupAdviserRequest;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchProject;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResearchClassGroupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        (new SyncOfficialFormCatalog)->handle();
        Storage::fake('local');
        Config::set('signatures.verification_key', 'test_secret_verification_key_32_bytes_long!!');
        Config::set('signatures.verification_key_version', 'v1');
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
            'status' => 'active',
        ]);
    }

    public function test_owning_facilitator_can_revise_the_canonical_title_without_resetting_group_records(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $otherFacilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Title Revision Group');
        $project = $this->attachCanonicalProject($group, $facilitator, 'Original Approved Research Title');

        $this->actingAs($facilitator)
            ->patchJson(route('facilitator.classes.groups.title.revise', [$researchClass, $group]), [
                'title' => 'Revised Approved Research Title',
                'reason' => 'The facilitator corrected the title to match the final study scope.',
            ])
            ->assertOk()
            ->assertJsonPath('project.title', 'Revised Approved Research Title');

        $this->assertSame('Revised Approved Research Title', $project->fresh()->title);
        $this->assertSame($project->research_group_id, $group->fresh()->research_group_id);
        $this->assertDatabaseHas('research_project_title_histories', [
            'research_project_id' => $project->getKey(),
            'previous_title' => 'Original Approved Research Title',
            'revised_title' => 'Revised Approved Research Title',
            'changed_by' => $facilitator->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'research-project.title-revised',
            'auditable_id' => $project->getKey(),
        ]);

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Canonical Research Title')
            ->assertSee('Revised Approved Research Title')
            ->assertSee('View title revision history');

        $this->actingAs($otherFacilitator)
            ->patchJson(route('facilitator.classes.groups.title.revise', [$researchClass, $group]), [
                'title' => 'Unauthorized Replacement Title',
                'reason' => 'This facilitator does not own the research class.',
            ])
            ->assertUnprocessable();

        $this->assertSame('Revised Approved Research Title', $project->fresh()->title);
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
            ->assertJsonPath('membership.student_id', $student->getKey())
            ->assertJsonPath('leader_student_id', $student->getKey());

        $this->assertSame($student->getKey(), $group->fresh()->leader_student_id);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'research-group.leader-assigned',
            'auditable_id' => $group->getKey(),
        ]);

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

    public function test_group_cannot_exceed_4_students(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        for ($i = 1; $i <= 4; $i++) {
            $student = $this->userWithRole('student-researcher');
            $enrollment = $this->enroll($researchClass, $student, 'active');

            $this->actingAs($facilitator)
                ->putJson(route('facilitator.classes.groups.students.assign', [
                    $researchClass, $group, $enrollment,
                ]))
                ->assertOk();
        }

        // Attempting to add 5th student must fail
        $fifthStudent = $this->userWithRole('student-researcher');
        $fifthEnrollment = $this->enroll($researchClass, $fifthStudent, 'active');

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.students.assign', [
                $researchClass, $group, $fifthEnrollment,
            ]))
            ->assertUnprocessable();

        $this->assertSame(4, ResearchClassGroupMember::query()->where('research_class_group_id', $group->getKey())->count());
    }

    public function test_facilitator_can_bulk_assign_multiple_students_to_a_group(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $student1 = $this->userWithRole('student-researcher');
        $student2 = $this->userWithRole('student-researcher');
        $student3 = $this->userWithRole('student-researcher');

        $enrollment1 = $this->enroll($researchClass, $student1, 'active');
        $enrollment2 = $this->enroll($researchClass, $student2, 'active');
        $enrollment3 = $this->enroll($researchClass, $student3, 'active');

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.groups.students.bulk-assign', $researchClass), [
                'group_id' => $group->getKey(),
                'enrollment_ids' => [$enrollment1->getKey(), $enrollment2->getKey(), $enrollment3->getKey()],
            ])
            ->assertOk()
            ->assertJsonPath('assigned_count', 3)
            ->assertJsonPath('leader_student_id', null)
            ->assertJsonPath('leader_assignment_required', true);

        $this->assertSame(3, ResearchClassGroupMember::query()->where('research_class_group_id', $group->getKey())->count());
        $this->assertNull($group->fresh()->leader_student_id);
    }

    public function test_adding_more_students_retains_the_automatically_assigned_sole_member_as_leader(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $firstStudent = $this->userWithRole('student-researcher');
        $secondStudent = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');
        $firstEnrollment = $this->enroll($researchClass, $firstStudent, 'active');
        $secondEnrollment = $this->enroll($researchClass, $secondStudent, 'active');

        foreach ([$firstEnrollment, $secondEnrollment] as $enrollment) {
            $this->actingAs($facilitator)
                ->putJson(route('facilitator.classes.groups.students.assign', [
                    $researchClass, $group, $enrollment,
                ]))
                ->assertOk();
        }

        $this->assertSame($firstStudent->getKey(), $group->fresh()->leader_student_id);
        $this->assertSame(2, $group->members()->count());
    }

    public function test_program_coordinator_can_send_adviser_request_and_adviser_can_accept_it_by_signing_res027(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $coordinator = $this->facultyWithRole('program-coordinator');
        $adviser = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $this->assertTrue($coordinator->can('dashboards.facilitator.view'));
        $this->assertTrue($coordinator->can('classes.assign-advisers'));

        $this->actingAs($coordinator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('adviser_request.status', 'pending');

        $this->assertDatabaseHas('research_class_group_adviser_requests', [
            'research_class_group_id' => $group->getKey(),
            'adviser_id' => $adviser->getKey(),
            'status' => 'pending',
        ]);

        $this->assertNull($group->fresh()->adviser_id);

        $request = ResearchClassGroupAdviserRequest::query()->sole();

        // Adviser accepts request
        $this->actingAs($adviser)
            ->patchJson(route('adviser.group-requests.respond', $request), [
                'decision' => 'accept',
            ])
            ->assertOk()
            ->assertJsonPath('adviser_request.status', 'pending')
            ->assertJsonStructure(['official_form_url']);

        $this->assertNull($group->fresh()->adviser_id);
        $this->signPendingAdviserInvitation($adviser, $request);

        $this->assertEquals($adviser->getKey(), $group->fresh()->adviser_id);
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertDatabaseHas('research_class_group_adviser_histories', [
            'research_class_group_id' => $group->getKey(),
            'adviser_id' => $adviser->getKey(),
            'assigned_by' => $coordinator->getKey(),
        ]);
    }

    public function test_group_cannot_have_multiple_simultaneous_pending_adviser_requests(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $coordinator = $this->facultyWithRole('program-coordinator');
        $adviser1 = $this->userWithRole('thesis-adviser');
        $adviser2 = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $this->actingAs($coordinator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser1->getKey(),
            ])
            ->assertOk();

        // Second request while first is pending must be blocked
        $this->actingAs($coordinator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser2->getKey(),
            ])
            ->assertUnprocessable();
    }

    public function test_research_facilitator_cannot_issue_an_adviser_invitation(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $adviser = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('research_class_group_adviser_requests', 0);
    }

    public function test_program_coordinator_can_cancel_pending_adviser_request(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $coordinator = $this->facultyWithRole('program-coordinator');
        $adviser = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $this->actingAs($coordinator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertOk();

        $adviserRequest = ResearchClassGroupAdviserRequest::query()->sole();

        $this->actingAs($coordinator)
            ->deleteJson(route('facilitator.classes.groups.adviser-requests.cancel', [$researchClass, $group, $adviserRequest]))
            ->assertOk();

        $this->assertEquals('cancelled', $adviserRequest->fresh()->status);
    }

    public function test_program_coordinator_cannot_remove_an_accepted_adviser_without_an_approved_change_request(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $coordinator = $this->facultyWithRole('program-coordinator');
        $adviser = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');

        $this->actingAs($coordinator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertOk();

        $adviserRequest = ResearchClassGroupAdviserRequest::query()->sole();

        $this->actingAs($adviser)
            ->patchJson(route('adviser.group-requests.respond', $adviserRequest), ['decision' => 'accept'])
            ->assertOk();

        $this->signPendingAdviserInvitation($adviser, $adviserRequest);

        $this->assertEquals($adviser->getKey(), $group->fresh()->adviser_id);

        // Active adviser changes must go through the audited RES-030 workflow.
        $this->actingAs($coordinator)
            ->deleteJson(route('facilitator.classes.groups.adviser.remove', [$researchClass, $group]))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'An active adviser cannot be removed directly. Submit and approve a RES-030 Adviser Change Request Form instead.');

        $this->assertEquals($adviser->getKey(), $group->fresh()->adviser_id);
        $this->assertNull(ResearchClassGroupAdviserHistory::query()->sole()->ended_at);
    }

    public function test_facilitator_can_disband_a_group_returning_members_to_unassigned(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $coordinator = $this->facultyWithRole('program-coordinator');
        $student = $this->userWithRole('student-researcher');
        $adviser = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Capstone Group 1');
        $enrollment = $this->enroll($researchClass, $student, 'active');

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.students.assign', [$researchClass, $group, $enrollment]))
            ->assertOk();

        $this->actingAs($coordinator)
            ->postJson(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertOk();

        // Disband group
        $this->actingAs($facilitator)
            ->deleteJson(route('facilitator.classes.groups.disband', [$researchClass, $group]))
            ->assertOk();

        $this->assertEquals('disbanded', $group->fresh()->status);
        $this->assertNotNull($group->fresh()->disbanded_at);
        $this->assertSame(0, ResearchClassGroupMember::query()->where('research_class_group_id', $group->getKey())->count());
        $this->assertEquals('cancelled', ResearchClassGroupAdviserRequest::query()->sole()->status);

        // Student is still active class enrollment
        $this->assertEquals('active', $enrollment->fresh()->status);
    }

    public function test_a_current_member_can_continue_the_same_project_without_losing_progress(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $firstStudent = $this->userWithRole('student-researcher');
        $continuingStudent = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Original Project');

        foreach ([$firstStudent, $continuingStudent] as $student) {
            $enrollment = $this->enroll($researchClass, $student, 'active');
            $this->actingAs($facilitator)
                ->putJson(route('facilitator.classes.groups.students.assign', [$researchClass, $group, $enrollment]))
                ->assertOk();
        }

        $group->update(['leader_student_id' => $firstStudent->getKey()]);
        $definition = MilestoneDefinition::query()->firstOrCreate(
            ['sequence' => 1],
            ['code' => 'continuation-test', 'name' => 'Project milestone'],
        );
        $milestone = ResearchGroupMilestone::query()->create([
            'research_class_group_id' => $group->getKey(),
            'milestone_definition_id' => $definition->getKey(),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.groups.continue-with-member', [$researchClass, $group]), [
                'student_id' => $continuingStudent->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('group.id', $group->getKey());

        $this->assertSame('active', $group->fresh()->status);
        $this->assertSame($continuingStudent->getKey(), $group->fresh()->leader_student_id);
        $this->assertSame($group->getKey(), $milestone->fresh()->research_class_group_id);
        $this->assertSame('completed', $milestone->fresh()->status->value);
        $this->assertDatabaseHas('research_class_group_members', [
            'research_class_group_id' => $group->getKey(), 'student_id' => $continuingStudent->getKey(),
        ]);
        $this->assertDatabaseMissing('research_class_group_members', [
            'research_class_group_id' => $group->getKey(), 'student_id' => $firstStudent->getKey(),
        ]);
        $this->assertDatabaseHas('research_class_group_member_histories', [
            'research_class_group_id' => $group->getKey(),
            'student_id' => $firstStudent->getKey(),
            'archive_reason' => 'group_restructured',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'research-group.continued-by-member', 'auditable_id' => $group->getKey(),
        ]);
    }

    public function test_a_former_member_can_restore_an_archived_project_and_keep_its_progress(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $continuingStudent = $this->userWithRole('student-researcher');
        $otherStudent = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Archived Project');

        foreach ([$continuingStudent, $otherStudent] as $student) {
            $enrollment = $this->enroll($researchClass, $student, 'active');
            $this->actingAs($facilitator)
                ->putJson(route('facilitator.classes.groups.students.assign', [$researchClass, $group, $enrollment]))
                ->assertOk();
        }

        $definition = MilestoneDefinition::query()->firstOrCreate(
            ['sequence' => 1],
            ['code' => 'restoration-test', 'name' => 'Archived milestone'],
        );
        $milestone = ResearchGroupMilestone::query()->create([
            'research_class_group_id' => $group->getKey(),
            'milestone_definition_id' => $definition->getKey(),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->actingAs($facilitator)
            ->deleteJson(route('facilitator.classes.groups.disband', [$researchClass, $group]))
            ->assertOk();
        $this->assertSame('disbanded', $group->fresh()->status);

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Archived Research Projects')
            ->assertSee('Restore &amp; Continue', false);

        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.groups.restore-with-member', [$researchClass, $group]), [
                'student_id' => $continuingStudent->getKey(),
            ])
            ->assertOk()
            ->assertJsonPath('group.id', $group->getKey());

        $this->assertTrue($group->fresh()->isActive());
        $this->assertSame($continuingStudent->getKey(), $group->fresh()->leader_student_id);
        $this->assertNull($group->fresh()->adviser_id);
        $this->assertSame($group->getKey(), $milestone->fresh()->research_class_group_id);
        $this->assertDatabaseHas('research_class_group_members', [
            'research_class_group_id' => $group->getKey(), 'student_id' => $continuingStudent->getKey(),
        ]);
        $this->assertDatabaseMissing('research_class_group_members', [
            'research_class_group_id' => $group->getKey(), 'student_id' => $otherStudent->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'research-group.restored-for-continuation', 'auditable_id' => $group->getKey(),
        ]);
    }

    public function test_an_unrelated_student_cannot_claim_an_archived_project(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $formerStudent = $this->userWithRole('student-researcher');
        $unrelatedStudent = $this->userWithRole('student-researcher');
        $researchClass = $this->createClass($facilitator);
        $group = $this->createGroup($researchClass, $facilitator, 'Archived Project');
        $this->enroll($researchClass, $unrelatedStudent, 'active');
        $enrollment = $this->enroll($researchClass, $formerStudent, 'active');

        $this->actingAs($facilitator)
            ->putJson(route('facilitator.classes.groups.students.assign', [$researchClass, $group, $enrollment]))
            ->assertOk();
        $this->actingAs($facilitator)
            ->deleteJson(route('facilitator.classes.groups.disband', [$researchClass, $group]))
            ->assertOk();
        $this->actingAs($facilitator)
            ->postJson(route('facilitator.classes.groups.restore-with-member', [$researchClass, $group]), [
                'student_id' => $unrelatedStudent->getKey(),
            ])
            ->assertUnprocessable();

        $this->assertSame('disbanded', $group->fresh()->status);
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
        $adviser = $this->userWithRole('thesis-adviser');
        $otherAdviser = $this->userWithRole('thesis-adviser');
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
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertForbidden();
    }

    public function test_unassigned_adviser_cannot_view_a_class(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $unassignedAdviser = $this->userWithRole('thesis-adviser');
        $researchClass = $this->createClass($facilitator);

        $this->actingAs($unassignedAdviser)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertForbidden();
    }

    public function test_adviser_dashboard_does_not_expose_facilitator_join_request_controls(): void
    {
        $adviser = $this->userWithRole('thesis-adviser');

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
        $coordinator = $this->facultyWithRole('program-coordinator');
        $student = $this->userWithRole('student-researcher');
        $adviser = $this->userWithRole('thesis-adviser');
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

        $this->actingAs($coordinator)
            ->post(route('facilitator.classes.groups.adviser-requests.store', [$researchClass, $group]), [
                'adviser_id' => $adviser->getKey(),
            ])
            ->assertRedirect(route('facilitator.classes.show', $researchClass));

        $adviserRequest = ResearchClassGroupAdviserRequest::query()->sole();

        $this->actingAs($adviser)
            ->patch(route('adviser.group-requests.respond', $adviserRequest), ['decision' => 'accept'])
            ->assertRedirect();

        $this->signPendingAdviserInvitation($adviser, $adviserRequest);

        $this->actingAs($facilitator)
            ->get(route('facilitator.classes.show', $researchClass))
            ->assertOk()
            ->assertSee('Browser Flow Student')
            ->assertSee('Browser Flow Group')
            ->assertSee('Browser Flow Adviser');
    }

    public function test_adviser_my_classes_displays_pending_requests_badge_and_assigned_group_members(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $adviser = $this->userWithRole('thesis-adviser');
        $otherAdviser = $this->userWithRole('thesis-adviser');
        $student1 = $this->userWithRole('student-researcher');
        $student2 = $this->userWithRole('student-researcher');
        $student1->update(['name' => 'Assigned Member One']);
        $student2->update(['name' => 'Assigned Member Two']);

        $researchClass = $this->createClass($facilitator);
        $enrollment1 = $this->enroll($researchClass, $student1, 'active');
        $enrollment2 = $this->enroll($researchClass, $student2, 'active');

        $assignedGroup = $this->createGroup($researchClass, $facilitator, 'Assigned Capstone Group');
        $assignedGroup->update(['adviser_id' => $adviser->getKey()]);

        foreach ([[$enrollment1, $student1], [$enrollment2, $student2]] as [$enr, $std]) {
            ResearchClassGroupMember::query()->create([
                'research_class_group_id' => $assignedGroup->getKey(),
                'research_class_id' => $researchClass->getKey(),
                'research_class_enrollment_id' => $enr->getKey(),
                'student_id' => $std->getKey(),
                'assigned_by' => $facilitator->getKey(),
            ]);
        }

        $assignedGroup->update(['leader_student_id' => $student1->getKey()]);

        $pendingGroup = $this->createGroup($researchClass, $facilitator, 'Pending Invitation Group');
        $adviserRequest = ResearchClassGroupAdviserRequest::query()->create([
            'research_class_group_id' => $pendingGroup->getKey(),
            'adviser_id' => $adviser->getKey(),
            'requested_by' => $facilitator->getKey(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Adviser B has a request that must not be counted or visible to Adviser A
        $otherGroup = $this->createGroup($researchClass, $facilitator, 'Other Adviser Group');
        ResearchClassGroupAdviserRequest::query()->create([
            'research_class_group_id' => $otherGroup->getKey(),
            'adviser_id' => $otherAdviser->getKey(),
            'requested_by' => $facilitator->getKey(),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'classes']))
            ->assertOk()
            ->assertSee('Pending Invitation Group')
            ->assertSee('Assigned Capstone Group')
            ->assertSee('Assigned Member One')
            ->assertSee('Assigned Member Two')
            ->assertSee('Group Leader')
            ->assertDontSee('Other Adviser Group');

        // Adviser A cannot accept Adviser B's request (422)
        $otherRequest = ResearchClassGroupAdviserRequest::query()->where('adviser_id', $otherAdviser->getKey())->sole();
        $this->actingAs($adviser)
            ->patchJson(route('adviser.group-requests.respond', $otherRequest), ['decision' => 'accept'])
            ->assertUnprocessable();
    }

    public function test_student_can_click_an_enrolled_class_and_see_only_their_group(): void
    {
        $facilitator = $this->userWithRole('research-facilitator');
        $adviser = $this->userWithRole('thesis-adviser');
        $otherAdviser = $this->userWithRole('thesis-adviser');
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
            ->assertSee('My Research')
            ->assertSee('Research Proposal')
            ->assertSee('Research Progress')
            ->assertSee('Consultation Records')
            ->assertSee('My Defense Schedule')
            ->assertSee('Official Forms')
            ->assertSee('Notifications')
            ->assertSee('Settings')
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

    private function facultyWithRole(string $role): User
    {
        $user = User::factory()->create(['user_type' => UserType::Faculty]);
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
            'status' => 'active',
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

    private function attachCanonicalProject(ResearchClassGroup $group, User $creator, string $title): ResearchProject
    {
        $now = now();
        $collegeId = DB::table('colleges')->insertGetId(['code' => 'TITLE-COLLEGE', 'name' => 'Title College', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $departmentId = DB::table('departments')->insertGetId(['college_id' => $collegeId, 'code' => 'TITLE-DEPT', 'name' => 'Title Department', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $programId = DB::table('programs')->insertGetId(['department_id' => $departmentId, 'code' => 'TITLE-PROG', 'name' => 'Title Program', 'degree_level' => 'Undergraduate', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $academicYearId = DB::table('academic_years')->insertGetId(['name' => '2098-2099', 'starts_at' => '2098-06-01', 'ends_at' => '2099-05-31', 'is_current' => true, 'created_at' => $now, 'updated_at' => $now]);
        $academicTermId = DB::table('academic_terms')->insertGetId(['academic_year_id' => $academicYearId, 'name' => 'First Semester', 'starts_at' => '2098-06-01', 'ends_at' => '2098-10-31', 'is_current' => true, 'created_at' => $now, 'updated_at' => $now]);
        $researchGroupId = DB::table('research_groups')->insertGetId(['program_id' => $programId, 'academic_term_id' => $academicTermId, 'name' => 'Canonical Title Group', 'created_by' => $creator->getKey(), 'created_at' => $now, 'updated_at' => $now]);

        $group->update(['research_group_id' => $researchGroupId]);

        return ResearchProject::query()->create([
            'research_group_id' => $researchGroupId,
            'title' => $title,
            'status' => 'active',
            'created_by' => $creator->getKey(),
            'approved_at' => $now,
        ]);
    }

    private function signPendingAdviserInvitation(User $adviser, ResearchClassGroupAdviserRequest $request): void
    {
        $adviser->update(['user_type' => UserType::Faculty]);
        $adviser->refresh();
        $signatureBytes = 'test-signature-'.$adviser->id;
        $signaturePath = "signatures/{$adviser->id}/test.png";
        Storage::disk('local')->put($signaturePath, $signatureBytes);
        UserSignature::query()->create([
            'user_id' => $adviser->id,
            'storage_disk' => 'local',
            'storage_path' => $signaturePath,
            'original_filename' => 'signature.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($signatureBytes),
            'content_sha256' => hash('sha256', $signatureBytes),
            'registered_at' => now(),
        ]);

        $invitation = OfficialFormInstance::query()
            ->where('source_type', ResearchClassGroupAdviserRequest::class)
            ->where('source_id', $request->getKey())
            ->whereHas('definition', fn ($query) => $query->where('code', 'RES-027'))
            ->sole();

        app(ApplyOfficialFormSignature::class)->handle(
            $adviser,
            $invitation->id,
            $invitation->current_version_id,
            'respond',
        );
    }
}

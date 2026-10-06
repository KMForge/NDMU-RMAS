<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\AssignClassDefenseCommittee;
use App\Modules\DefenseScheduling\Actions\AssignGroupDefenseCommittee;
use App\Modules\DefenseScheduling\Queries\GetClassCommitteeAssignments;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DefenseCommitteeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $adviser;

    private User $chairperson;

    private User $panel1;

    private User $panel2;

    private ResearchClass $researchClass;

    private ResearchClassGroup $classGroup1;

    private ResearchClassGroup $classGroup2;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'classes.manage-groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'guard_name' => 'web']);

        $this->facilitator = User::factory()->create();
        $this->facilitator->givePermissionTo('defenses.manage');
        $this->facilitator->givePermissionTo('classes.manage-groups');

        $this->adviser = User::factory()->create(['name' => 'Prof Adviser']);
        $this->adviser->givePermissionTo('evaluations.create');

        $this->chairperson = User::factory()->create(['name' => 'Prof Chair']);
        $this->chairperson->givePermissionTo('evaluations.create');

        $this->panel1 = User::factory()->create(['name' => 'Dr Panelist 1']);
        $this->panel1->givePermissionTo('evaluations.create');

        $this->panel2 = User::factory()->create(['name' => 'Engr Panelist 2']);
        $this->panel2->givePermissionTo('evaluations.create');

        $this->researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'CS-401 Section A',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $this->classGroup1 = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Group 1',
            'leader_student_id' => $this->facilitator->id,
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $this->classGroup2 = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Group 2',
            'leader_student_id' => $this->facilitator->id,
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);
    }

    public function test_assign_class_defense_committee_propagates_to_groups(): void
    {
        $action = app(AssignClassDefenseCommittee::class);

        $committee = $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id,
            applyToGroupIds: null,
            overwriteCustom: false
        );

        $this->assertDatabaseHas('research_class_panel_committees', [
            'id' => $committee->id,
            'research_class_id' => $this->researchClass->id,
            'defense_type' => 'proposal_defense',
            'chairperson_id' => $this->chairperson->id,
        ]);

        $this->assertCount(2, $committee->members);

        // Verify both groups inherited the committee
        $this->assertDatabaseHas('research_group_panel_committees', [
            'research_class_group_id' => $this->classGroup1->id,
            'defense_type' => 'proposal_defense',
            'chairperson_id' => $this->chairperson->id,
            'is_custom' => false,
        ]);

        $this->assertDatabaseHas('research_group_panel_committees', [
            'research_class_group_id' => $this->classGroup2->id,
            'defense_type' => 'proposal_defense',
            'chairperson_id' => $this->chairperson->id,
            'is_custom' => false,
        ]);
    }

    public function test_adviser_is_eligible_to_be_assigned_as_chairperson(): void
    {
        $action = app(AssignClassDefenseCommittee::class);

        // The adviser of the group is assigned as Chairperson
        $committee = $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->adviser->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );

        $this->assertEquals($this->adviser->id, $committee->chairperson_id);

        $groupCommittee = ResearchGroupPanelCommittee::where('research_class_group_id', $this->classGroup1->id)->first();
        $this->assertNotNull($groupCommittee);
        $this->assertEquals($this->adviser->id, $groupCommittee->chairperson_id);
    }

    public function test_custom_group_assignments_are_preserved_when_overwrite_custom_is_false(): void
    {
        // 1. First assign custom committee to Group 1
        $groupAction = app(AssignGroupDefenseCommittee::class);
        $customOtherChair = User::factory()->create();
        $customOtherChair->givePermissionTo('evaluations.create');

        $groupAction->execute(
            researchClassGroupId: $this->classGroup1->id,
            defenseType: 'proposal_defense',
            chairpersonId: $customOtherChair->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );

        // 2. Now run class-level assignment with overwriteCustom = false
        $classAction = app(AssignClassDefenseCommittee::class);
        $classAction->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id,
            applyToGroupIds: null,
            overwriteCustom: false
        );

        // Group 1 should still have custom chairperson
        $group1Committee = ResearchGroupPanelCommittee::where('research_class_group_id', $this->classGroup1->id)->first();
        $this->assertEquals($customOtherChair->id, $group1Committee->chairperson_id);
        $this->assertTrue($group1Committee->is_custom);

        // Group 2 should have the class chairperson
        $group2Committee = ResearchGroupPanelCommittee::where('research_class_group_id', $this->classGroup2->id)->first();
        $this->assertEquals($this->chairperson->id, $group2Committee->chairperson_id);
        $this->assertFalse($group2Committee->is_custom);
    }

    public function test_custom_group_assignments_are_overwritten_when_overwrite_custom_is_true(): void
    {
        // 1. First assign custom committee to Group 1
        $groupAction = app(AssignGroupDefenseCommittee::class);
        $customOtherChair = User::factory()->create();
        $customOtherChair->givePermissionTo('evaluations.create');

        $groupAction->execute(
            researchClassGroupId: $this->classGroup1->id,
            defenseType: 'proposal_defense',
            chairpersonId: $customOtherChair->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );

        // 2. Run class-level assignment with overwriteCustom = true
        $classAction = app(AssignClassDefenseCommittee::class);
        $classAction->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id,
            applyToGroupIds: null,
            overwriteCustom: true
        );

        // Group 1 should now be overwritten by class chairperson and is_custom = false
        $group1Committee = ResearchGroupPanelCommittee::where('research_class_group_id', $this->classGroup1->id)->first();
        $this->assertEquals($this->chairperson->id, $group1Committee->chairperson_id);
        $this->assertFalse($group1Committee->is_custom);
    }

    public function test_assigning_duplicate_members_fails_validation(): void
    {
        $this->expectException(ValidationException::class);

        $action = app(AssignClassDefenseCommittee::class);

        // Chairperson and Panel 1 are the same user
        $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->chairperson->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );
    }

    public function test_group_assignment_issues_individual_res028_invitations_and_reports_response_statuses(): void
    {
        $this->seed(RolePermissionSeeder::class);
        (new SyncOfficialFormCatalog)->handle();

        $coordinator = User::factory()->create(['user_type' => UserType::Faculty]);
        $coordinator->assignRole('program-coordinator');
        foreach ([$this->chairperson, $this->panel1, $this->panel2] as $panelist) {
            $panelist->update(['user_type' => UserType::Faculty]);
            $panelist->assignRole('panelist');
        }

        app(AssignGroupDefenseCommittee::class)->execute(
            researchClassGroupId: $this->classGroup1->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id,
        );

        $invitations = OfficialFormInstance::query()
            ->where('research_class_group_id', $this->classGroup1->id)
            ->whereHas('definition', fn ($query) => $query->where('code', 'RES-028'))
            ->with('actorAssignments')
            ->get();

        $this->assertCount(3, $invitations);
        $this->assertEqualsCanonicalizing(
            [$this->chairperson->id, $this->panel1->id, $this->panel2->id],
            $invitations->flatMap->actorAssignments->pluck('user_id')->all(),
        );

        $panelOneInvitation = $invitations->first(fn (OfficialFormInstance $instance): bool => $instance->actorAssignments->contains('user_id', $this->panel1->id));
        $this->actingAs($this->panel1)
            ->post(route('official-forms.workspace.panel-invitation.decline', $panelOneInvitation))
            ->assertRedirect(route('official-forms.workspace.index'));

        $statuses = app(GetClassCommitteeAssignments::class)
            ->forClass($this->researchClass, 'proposal_defense')['groups']
            ->firstWhere('id', $this->classGroup1->id);

        $this->assertSame('pending', $statuses['chairperson_invitation_status']);
        $this->assertSame('rejected', $statuses['member_1_invitation_status']);
        $this->assertSame('pending', $statuses['member_2_invitation_status']);

        $chairInvitation = $invitations->first(fn (OfficialFormInstance $instance): bool => $instance->actorAssignments->contains('user_id', $this->chairperson->id));
        $chairInvitation->update(['status' => 'approved']);
        $accepted = app(GetClassCommitteeAssignments::class)
            ->forClass($this->researchClass, 'proposal_defense')['groups']
            ->firstWhere('id', $this->classGroup1->id);
        $this->assertSame('accepted', $accepted['chairperson_invitation_status']);
        $this->assertSame($this->chairperson->id, $accepted['chairperson_id']);
        $this->assertSame($this->chairperson->name, $accepted['chairperson_name']);
        $this->assertSame($this->panel1->id, $accepted['member_1_id']);
        $this->assertSame($this->panel2->id, $accepted['member_2_id']);
    }

    public function test_swapping_group_panel_members_does_not_violate_unique_constraint(): void
    {
        // First assignment: member 1 = panel1, member 2 = panel2
        $action = app(AssignGroupDefenseCommittee::class);
        $action->execute(
            researchClassGroupId: $this->classGroup1->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );

        // Now swap them: member 1 = panel2, member 2 = panel1
        // Previously this threw Unique violation "rg_panel_members_user_unique"
        $updated = $action->execute(
            researchClassGroupId: $this->classGroup1->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel2->id,
            panelMember2Id: $this->panel1->id,
            assignedByUserId: $this->facilitator->id
        );

        $member1 = $updated->members->firstWhere('panel_position', 'member_1');
        $member2 = $updated->members->firstWhere('panel_position', 'member_2');

        $this->assertSame($this->panel2->id, $member1->user_id);
        $this->assertSame($this->panel1->id, $member2->user_id);
    }
}

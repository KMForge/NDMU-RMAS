<?php

namespace Tests\Feature;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\AssignClassDefenseCommittee;
use App\Modules\DefenseScheduling\Actions\AssignGroupDefenseCommittee;
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
}

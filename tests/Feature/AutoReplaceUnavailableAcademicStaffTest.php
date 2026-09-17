<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupAdviserHistory;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\ResearchGroupPanelMember;
use App\Models\User;
use App\Modules\UserManagement\Actions\ManageUserAccount;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AutoReplaceUnavailableAcademicStaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_suspending_faculty_automatically_replaces_current_adviser_and_panel_assignments(): void
    {
        $admin = User::factory()->create(['user_type' => UserType::Admin]);
        $admin->assignRole('system-administrator');

        $unavailable = $this->faculty('Unavailable Faculty');
        $unavailable->givePermissionTo(['classes.serve-as-adviser', 'evaluations.create']);

        $replacementAdviser = $this->faculty('Replacement Adviser');
        $replacementAdviser->givePermissionTo('classes.serve-as-adviser');

        $replacementPanelist = $this->faculty('Replacement Panelist');
        $replacementPanelist->givePermissionTo('evaluations.create');

        $memberOne = $this->faculty('Existing Panel One');
        $memberOne->givePermissionTo('evaluations.create');
        $memberTwo = $this->faculty('Existing Panel Two');
        $memberTwo->givePermissionTo('evaluations.create');

        $researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $admin->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'ITCAP 102 - IT4A',
            'join_code_hash' => hash('sha256', 'DRYRUN'),
            'join_code_encrypted' => 'DRYRUN',
            'is_active' => true,
        ]);
        $group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Replacement Flow Group',
            'leader_student_id' => $admin->id,
            'adviser_id' => $unavailable->id,
            'created_by' => $admin->id,
            'status' => 'active',
        ]);
        ResearchClassGroupAdviserHistory::query()->create([
            'research_class_group_id' => $group->id,
            'adviser_id' => $unavailable->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now()->subDay(),
        ]);

        $committee = ResearchGroupPanelCommittee::query()->create([
            'research_class_group_id' => $group->id,
            'defense_type' => 'proposal_defense',
            'chairperson_id' => $unavailable->id,
            'is_custom' => true,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        ResearchGroupPanelMember::query()->create([
            'committee_id' => $committee->id,
            'user_id' => $memberOne->id,
            'panel_position' => 'member_1',
        ]);
        ResearchGroupPanelMember::query()->create([
            'committee_id' => $committee->id,
            'user_id' => $memberTwo->id,
            'panel_position' => 'member_2',
        ]);

        $defense = Defense::query()->create([
            'research_class_group_id' => $group->id,
            'defense_type' => 'proposal_defense',
            'status' => 'scheduled',
            'created_by' => $admin->id,
        ]);
        foreach ([
            [$unavailable->id, 'chairperson'],
            [$memberOne->id, 'member_1'],
            [$memberTwo->id, 'member_2'],
        ] as [$userId, $position]) {
            DefensePanelAssignment::query()->create([
                'defense_id' => $defense->id,
                'user_id' => $userId,
                'panel_position' => $position,
                'assigned_by' => $admin->id,
                'assigned_at' => now(),
            ]);
        }

        app(ManageUserAccount::class)->suspend($unavailable, $admin);

        $this->assertSame(AccountStatus::Suspended, $unavailable->refresh()->status);
        $this->assertSame($replacementAdviser->id, $group->refresh()->adviser_id);
        $this->assertSame($replacementPanelist->id, $committee->refresh()->chairperson_id);
        $this->assertDatabaseHas('research_class_group_adviser_histories', [
            'research_class_group_id' => $group->id,
            'adviser_id' => $replacementAdviser->id,
            'ended_at' => null,
        ]);
        $this->assertDatabaseHas('defense_panel_assignments', [
            'defense_id' => $defense->id,
            'user_id' => $replacementPanelist->id,
            'panel_position' => 'chairperson',
            'ended_at' => null,
        ]);
        $this->assertDatabaseMissing('defense_panel_assignments', [
            'defense_id' => $defense->id,
            'user_id' => $unavailable->id,
            'ended_at' => null,
        ]);
        $this->assertTrue(AuditLog::query()->where('event', 'academic-assignment.auto-replaced')->exists());
    }

    public function test_suspension_is_rolled_back_when_an_active_group_has_no_eligible_replacement_adviser(): void
    {
        $admin = User::factory()->create(['user_type' => UserType::Admin]);
        $admin->assignRole('system-administrator');
        $unavailable = $this->faculty('Only Eligible Adviser');
        $unavailable->givePermissionTo('classes.serve-as-adviser');

        $researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $admin->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'ITCAP 103 - IT4B',
            'join_code_hash' => hash('sha256', 'NO-BACKUP'),
            'join_code_encrypted' => 'NO-BACKUP',
            'is_active' => true,
        ]);
        ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'No Replacement Group',
            'leader_student_id' => $admin->id,
            'adviser_id' => $unavailable->id,
            'created_by' => $admin->id,
            'status' => 'active',
        ]);

        try {
            app(ManageUserAccount::class)->suspend($unavailable, $admin);
            $this->fail('Suspension unexpectedly succeeded without a replacement adviser.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('replacement', $exception->errors());
        }

        $this->assertSame(AccountStatus::Active, $unavailable->refresh()->status);
        $this->assertNotNull($unavailable->approved_at);
    }

    public function test_completed_defense_panel_history_is_not_rewritten_when_faculty_becomes_unavailable(): void
    {
        $admin = User::factory()->create(['user_type' => UserType::Admin]);
        $admin->assignRole('system-administrator');
        $unavailable = $this->faculty('Former Panelist');
        $unavailable->givePermissionTo('evaluations.create');

        $researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $admin->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'ITCAP 104 - IT4C',
            'join_code_hash' => hash('sha256', 'HISTORY'),
            'join_code_encrypted' => 'HISTORY',
            'is_active' => true,
        ]);
        $group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Completed Defense Group',
            'leader_student_id' => $admin->id,
            'created_by' => $admin->id,
            'status' => 'active',
        ]);
        $defense = Defense::query()->create([
            'research_class_group_id' => $group->id,
            'defense_type' => 'proposal_defense',
            'status' => 'completed',
            'created_by' => $admin->id,
            'completed_at' => now(),
            'completed_by' => $admin->id,
        ]);
        $assignment = DefensePanelAssignment::query()->create([
            'defense_id' => $defense->id,
            'user_id' => $unavailable->id,
            'panel_position' => 'member_1',
            'assigned_by' => $admin->id,
            'assigned_at' => now()->subDay(),
        ]);

        app(ManageUserAccount::class)->suspend($unavailable, $admin);

        $this->assertSame(AccountStatus::Suspended, $unavailable->refresh()->status);
        $this->assertNull($assignment->refresh()->ended_at);
        $this->assertSame($unavailable->id, $assignment->user_id);
    }

    private function faculty(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'user_type' => UserType::Faculty,
            'department' => 'Computer Studies Department',
        ]);
    }
}

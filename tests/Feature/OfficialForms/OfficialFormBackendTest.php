<?php

namespace Tests\Feature\OfficialForms;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\OfficialForms\Actions\ApproveOfficialForm;
use App\Modules\OfficialForms\Actions\AssignOfficialFormActor;
use App\Modules\OfficialForms\Actions\CertifyOfficialForm;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SubmitOfficialFormVersion;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class OfficialFormBackendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        (new SyncOfficialFormCatalog)->handle();
    }

    private function createGroup(?User $leader = null, ?User $adviser = null): ResearchClassGroup
    {
        $facilitator = User::factory()->create(['user_type' => 'faculty']);
        $facilitator->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit', 'forms.res-033.endorse', 'forms.res-045.certify', 'forms.res-036.evaluate');

        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $leaderUser = $leader ?? User::factory()->create(['user_type' => 'student']);
        $leaderUser->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit', 'forms.res-031.fill', 'forms.res-033.endorse', 'forms.res-045.certify', 'forms.res-046.certify', 'forms.res-042.submit');

        if ($adviser !== null) {
            $adviser->givePermissionTo('forms.res-033.endorse', 'forms.res-026.approve');
        }

        return ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'name' => 'Group '.bin2hex(random_bytes(3)),
            'leader_student_id' => $leaderUser->id,
            'adviser_id' => $adviser?->id,
            'created_by' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'status' => 'active',
        ]);
    }

    public function test_can_create_group_owned_form_instance(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit');
        $group = $this->createGroup(leader: $student);
        $student->update(['research_class_group_id' => $group->id]);

        $action = new CreateOfficialFormInstance;
        $instance = $action->handle(
            initiator: $student,
            formCode: 'RES-026',
            groupId: $group->id,
            payload: ['proposed_title' => 'AI-Powered Research System']
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame('draft', $instance->status);
        $this->assertSame(1, $instance->currentVersion->version_number);
        $this->assertSame('AI-Powered Research System', $instance->currentVersion->payload['proposed_title']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.created',
            'auditable_id' => $instance->id,
        ]);
    }

    public function test_res036_creation_blocked_pending_phase21_panel_assignment(): void
    {
        $panelist = User::factory()->create(['user_type' => 'faculty']);
        $panelist->givePermissionTo('forms.res-036.evaluate');
        $group = $this->createGroup();

        $action = new CreateOfficialFormInstance;

        try {
            $action->handle($panelist, 'RES-036', $group->id, actorUserId: $panelist->id);
            $this->fail('Expected InvalidArgumentException for RES-036 creation.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('RES-036 is blocked pending the authoritative Defense Panel Assignment source from Phase 21', $e->getMessage());
        }

        $this->assertDatabaseMissing('official_form_instances', [
            'research_class_group_id' => $group->id,
            'initiated_by' => $panelist->id,
        ]);
    }

    public function test_res037_creation_blocked_pending_phase21_evaluations(): void
    {
        $panelist = User::factory()->create(['user_type' => 'faculty']);
        $panelist->givePermissionTo('forms.res-037.sign');
        $group = $this->createGroup();

        $action = new CreateOfficialFormInstance;

        $this->expectException(InvalidArgumentException::class);
        $action->handle($panelist, 'RES-037', $group->id, actorUserId: $panelist->id);
    }

    public function test_res043a_requires_pre_existing_validator_assignment_on_res042_source(): void
    {
        $validatorA = User::factory()->create(['user_type' => 'faculty']);
        $validatorA->givePermissionTo('forms.res-043a.validate');
        $validatorB = User::factory()->create(['user_type' => 'faculty']);
        $validatorB->givePermissionTo('forms.res-043a.validate');
        $group = $this->createGroup();

        $action = new CreateOfficialFormInstance;

        // 1. Create valid RES-042 request instance
        $res042 = $action->handle($group->leader, 'RES-042', $group->id);

        // 2. Unassigned validator attempting RES-043A linked to RES-042 is blocked
        try {
            $action->handle(
                initiator: $validatorA,
                formCode: 'RES-043A',
                groupId: $group->id,
                sourceType: OfficialFormInstance::class,
                sourceId: $res042->id,
                actorUserId: $validatorA->id
            );
            $this->fail('Expected InvalidArgumentException for unassigned validator.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('is not an assigned instrument validator for the source RES-042 validation request', $e->getMessage());
        }

        // 3. Assign Validator A to RES-042 request instance
        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $res042, $validatorA->id, 'instrument_validator');

        // 4. Assigned Validator A creating RES-043A linked to RES-042 succeeds
        $res043a = $action->handle(
            initiator: $validatorA,
            formCode: 'RES-043A',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $res042->id,
            actorUserId: $validatorA->id
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $res043a);

        // 5. Attempting RES-043A with wrong actorUserId (Validator B) is blocked
        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $validatorA,
            formCode: 'RES-043A',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $res042->id,
            actorUserId: $validatorB->id
        );
    }

    public function test_res045_language_editor_requires_pre_existing_assignment_and_prevents_cross_group(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $editor->givePermissionTo('forms.res-045.certify');
        $groupA = $this->createGroup();
        $groupB = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;

        // 1. Create RES-029 or RES-045 on Group A
        $instanceA = $createAction->handle($groupA->leader, 'RES-045', $groupA->id);

        // 2. Assign editor to Group A instance
        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($groupA->creator, $instanceA, $editor->id, 'language_editor');

        // 3. Editor can certify RES-045 for Group A
        $certifyAction = new CertifyOfficialForm;
        $certifiedInstance = $certifyAction->handle($editor, $instanceA, ['notes' => 'Approved']);
        $this->assertSame('completed', $certifiedInstance->status);

        // 4. Group B RES-045 without assignment for editor is denied
        $instanceB = $createAction->handle($groupB->leader, 'RES-045', $groupB->id);
        $this->expectException(InvalidArgumentException::class);
        $certifyAction->handle($editor, $instanceB, ['notes' => 'Cross-group attempt']);
    }

    public function test_generic_actor_assignment_does_not_authorize_wrong_specialist_action(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $editor->givePermissionTo('forms.res-045.certify', 'forms.res-046.certify');
        $group = $this->createGroup();

        // Create RES-045 and assign user as language_editor
        $createAction = new CreateOfficialFormInstance;
        $instance045 = $createAction->handle($group->leader, 'RES-045', $group->id);
        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $instance045, $editor->id, 'language_editor');

        // User is language_editor, but attempting RES-046 (technical_editor) without technical_editor assignment throws exception
        $instance046 = $createAction->handle($group->leader, 'RES-046', $group->id);
        $certifyAction = new CertifyOfficialForm;
        $this->expectException(InvalidArgumentException::class);
        $certifyAction->handle($editor, $instance046, ['notes' => 'Wrong actor type attempt']);
    }

    public function test_direct_action_creation_by_unauthorized_user_throws_exception(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        // Student lacks forms.res-026.fill permission
        $group = $this->createGroup();
        $student->update(['research_class_group_id' => $group->id]);

        $action = new CreateOfficialFormInstance;
        $this->expectException(InvalidArgumentException::class);
        $action->handle($student, 'RES-026', $group->id);
    }

    public function test_student_attempting_to_create_form_for_wrong_group_throws_exception(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit');
        $groupA = $this->createGroup(leader: $student);
        $groupB = $this->createGroup();
        $student->update(['research_class_group_id' => $groupA->id]);

        $action = new CreateOfficialFormInstance;
        $this->expectException(InvalidArgumentException::class);
        $action->handle($student, 'RES-026', $groupB->id);
    }

    public function test_assigner_authorization_prevents_unauthorized_faculty_assignment(): void
    {
        $randomFaculty = User::factory()->create(['user_type' => 'faculty']);
        $randomFaculty->givePermissionTo('forms.res-045.certify');
        $targetEditor = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($group->leader, 'RES-045', $group->id);

        $assignAction = new AssignOfficialFormActor;
        $this->expectException(InvalidArgumentException::class);
        $assignAction->handle($randomFaculty, $instance, $targetEditor->id, 'language_editor');
    }

    public function test_direct_submission_permission_enforcement(): void
    {
        $leaderStudent = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup(leader: $leaderStudent);

        $unauthorizedStudent = User::factory()->create(['user_type' => 'student']);
        $unauthorizedStudent->update(['research_class_group_id' => $group->id]);

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($group->leader, 'RES-026', $group->id);

        $submitAction = new SubmitOfficialFormVersion;
        $this->expectException(InvalidArgumentException::class);
        $submitAction->handle($unauthorizedStudent, $instance, ['title' => 'Unauthorized update']);
    }

    public function test_admin_system_manage_does_not_act_as_academic_approver_without_academic_context(): void
    {
        $admin = User::factory()->create(['user_type' => 'faculty']);
        $admin->givePermissionTo('users.manage', 'forms.res-033.endorse');
        $group = $this->createGroup(); // Admin is NOT group adviser

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($group->leader, 'RES-033', $group->id, contextKey: 'proposal_defense');

        $approveAction = new ApproveOfficialForm;
        $this->expectException(InvalidArgumentException::class);
        $approveAction->handle($admin, $instance, ['remarks' => 'Admin override attempt']);
    }

    public function test_admin_system_manage_cannot_initiate_group_form_without_group_context(): void
    {
        $admin = User::factory()->create(['user_type' => 'faculty']);
        $admin->givePermissionTo('users.manage', 'forms.res-026.fill', 'forms.res-026.submit');
        $group = $this->createGroup(); // Admin is NOT student/adviser in group

        $createAction = new CreateOfficialFormInstance;
        $this->expectException(InvalidArgumentException::class);
        $createAction->handle($admin, 'RES-026', $group->id);
    }
}

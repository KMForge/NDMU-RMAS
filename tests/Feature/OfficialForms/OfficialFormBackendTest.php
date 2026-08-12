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

    public function test_per_actor_assignment_persisted_atomically_at_creation_and_uses_persisted_actor_identity(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $adviser->givePermissionTo('forms.res-036.evaluate');
        $panelistA = User::factory()->create(['user_type' => 'faculty']);
        $panelistB = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);

        $action = new CreateOfficialFormInstance;
        // Adviser creates evaluation for Panelist A
        $instance1 = $action->handle(
            initiator: $adviser,
            formCode: 'RES-036',
            groupId: $group->id,
            contextKey: 'proposal_defense',
            actorUserId: $panelistA->id
        );

        $this->assertSame($adviser->id, $instance1->initiated_by);
        $this->assertDatabaseHas('official_form_actor_assignments', [
            'official_form_instance_id' => $instance1->id,
            'user_id' => $panelistA->id,
            'actor_type' => 'panelist',
            'status' => 'active',
        ]);

        // Second creation for Panelist B in same defense context is allowed
        $instance2 = $action->handle(
            initiator: $adviser,
            formCode: 'RES-036',
            groupId: $group->id,
            contextKey: 'proposal_defense',
            actorUserId: $panelistB->id
        );
        $this->assertInstanceOf(OfficialFormInstance::class, $instance2);

        // Duplicate creation for Panelist A in same defense context is blocked
        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $adviser,
            formCode: 'RES-036',
            groupId: $group->id,
            contextKey: 'proposal_defense',
            actorUserId: $panelistA->id
        );
    }

    public function test_validator_source_linkage_requires_res042_request_source(): void
    {
        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');
        $group = $this->createGroup();

        $action = new CreateOfficialFormInstance;

        // 1. Without source throws exception
        try {
            $action->handle($validator, 'RES-043A', $group->id, actorUserId: $validator->id);
            $this->fail('Expected InvalidArgumentException for missing source.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('authoritative RES-042 validation request source', $e->getMessage());
        }

        // 2. Create valid RES-042 request instance
        $res042 = $action->handle($group->leader, 'RES-042', $group->id);

        // 3. Create RES-043A linked to RES-042 succeeds
        $res043a = $action->handle(
            initiator: $validator,
            formCode: 'RES-043A',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $res042->id,
            actorUserId: $validator->id
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $res043a);
        $this->assertSame(OfficialFormInstance::class, $res043a->source_type);
        $this->assertSame($res042->id, $res043a->source_id);

        // 4. Duplicate creation for same validator + same RES-042 is blocked
        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $validator,
            formCode: 'RES-043A',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $res042->id,
            actorUserId: $validator->id
        );
    }

    public function test_actor_type_isolation_prevents_cross_actor_actions(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $editor->givePermissionTo('forms.res-045.certify', 'forms.res-046.certify');
        $group = $this->createGroup();

        // Create RES-045 form instance
        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($group->leader, 'RES-045', $group->id);

        // Assign user as language_editor
        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $instance, $editor->id, 'language_editor');

        // Language Editor can certify RES-045
        $certifyAction = new CertifyOfficialForm;
        $certifiedInstance = $certifyAction->handle($editor, $instance, ['notes' => 'Language approved']);
        $this->assertSame('completed', $certifiedInstance->status);

        // Create RES-046 Technical Editor form instance
        $res046Instance = $createAction->handle($group->leader, 'RES-046', $group->id);

        // Without technical_editor assignment, certifying RES-046 is denied
        $this->expectException(InvalidArgumentException::class);
        $certifyAction->handle($editor, $res046Instance, ['notes' => 'Technical approval']);
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

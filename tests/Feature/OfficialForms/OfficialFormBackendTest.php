<?php

namespace Tests\Feature\OfficialForms;

use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\OfficialForms\Actions\ApproveOfficialForm;
use App\Modules\OfficialForms\Actions\AssignOfficialFormActor;
use App\Modules\OfficialForms\Actions\CertifyOfficialForm;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
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
        $leaderUser->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit', 'forms.res-031.fill', 'forms.res-033.endorse', 'forms.res-045.certify', 'forms.res-046.certify', 'forms.res-042.submit', 'forms.res-040.endorse', 'forms.res-041.fill', 'forms.res-041.endorse', 'forms.res-043a.validate');

        if ($adviser !== null) {
            $adviser->givePermissionTo('forms.res-033.endorse', 'forms.res-026.approve', 'forms.res-040.endorse');
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

    public function test_admin_users_manage_cannot_certify_res045_without_language_editor_assignment(): void
    {
        $admin = User::factory()->create(['user_type' => 'faculty']);
        $admin->givePermissionTo('users.manage', 'forms.res-045.certify');
        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($group->leader, 'RES-045', $group->id);

        $certifyAction = new CertifyOfficialForm;
        $this->expectException(InvalidArgumentException::class);
        $certifyAction->handle($admin, $instance, ['notes' => 'Admin certification attempt']);
    }

    public function test_initiator_cannot_certify_res045_without_language_editor_assignment(): void
    {
        $initiator = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup(leader: $initiator);

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($initiator, 'RES-045', $group->id);

        // Give initiator certification permission but NO language_editor assignment
        $initiator->givePermissionTo('forms.res-045.certify');

        $certifyAction = new CertifyOfficialForm;
        $this->expectException(InvalidArgumentException::class);
        $certifyAction->handle($initiator, $instance, ['notes' => 'Initiator certification attempt']);
    }

    public function test_generic_actor_assignment_does_not_authorize_wrong_specialist_action(): void
    {
        $user = User::factory()->create(['user_type' => 'faculty']);
        $user->givePermissionTo('forms.res-045.certify', 'forms.res-046.certify');
        $group = $this->createGroup();

        // Assign user as language_editor on RES-045 instance
        $createAction = new CreateOfficialFormInstance;
        $instance045 = $createAction->handle($group->leader, 'RES-045', $group->id);
        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $instance045, $user->id, 'language_editor');

        // Create RES-046 instance (no technical_editor assignment)
        $instance046 = $createAction->handle($group->leader, 'RES-046', $group->id);

        // User is language_editor on Group A, but attempting Certify on RES-046 (requires technical_editor) is denied
        $certifyAction = new CertifyOfficialForm;
        $this->expectException(InvalidArgumentException::class);
        $certifyAction->handle($user, $instance046, ['notes' => 'Wrong actor type attempt on 046']);
    }

    public function test_res040_action_specific_actor_rules(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $adviser->givePermissionTo('forms.res-040.endorse');

        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-040.receive');

        $group = $this->createGroup(adviser: $adviser);

        $createAction = new CreateOfficialFormInstance;
        $instance1 = $createAction->handle($adviser, 'RES-040', $group->id);

        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $instance1, $instructor->id, 'research_instructor');

        $approveAction = new ApproveOfficialForm;

        // Adviser can endorse
        $endorsedInstance = $approveAction->handle($adviser, $instance1, ['notes' => 'Endorsed'], 'endorsed', 'endorse');
        $this->assertSame('endorsed', $endorsedInstance->status);

        // Research Instructor can receive
        $receivedInstance = $approveAction->handle($instructor, $instance1, ['notes' => 'Received'], 'approved', 'receive');
        $this->assertSame('approved', $receivedInstance->status);

        // Instructor CANNOT perform adviser endorsement on a new draft instance
        $instance2 = $createAction->handle($adviser, 'RES-040', $group->id, contextKey: 'second_submission');
        $assignAction->handle($group->creator, $instance2, $instructor->id, 'research_instructor');

        try {
            $approveAction->handle($instructor, $instance2, ['notes' => 'Attempt'], 'endorsed', 'endorse');
            $this->fail('Expected InvalidArgumentException for instructor endorsement.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not contextually authorized', $e->getMessage());
        }
    }

    public function test_res041_action_specific_actor_rules(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-041.fill', 'forms.res-041.endorse');

        $coordinator = User::factory()->create(['user_type' => 'faculty']);
        $coordinator->givePermissionTo('forms.res-041.receive');

        $group = $this->createGroup(adviser: $adviser);
        $class = $group->researchClass;
        $createAction = new CreateOfficialFormInstance;
        $res040 = $createAction->handle($adviser, 'RES-040', $group->id);

        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $res040, $instructor->id, 'research_instructor');

        $instance = $createAction->handle($instructor, 'RES-041', classId: $class->id);
        $assignAction->handle($class->facilitator, $instance, $instructor->id, 'research_instructor');
        $assignAction->handle($class->facilitator, $instance, $coordinator->id, 'program_coordinator');

        $approveAction = new ApproveOfficialForm;

        try {
            $approveAction->handle($coordinator, $instance, [], 'approved', 'receive');
            $this->fail('Expected coordinator receive to fail before instructor endorsement.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('cannot be performed', $exception->getMessage());
        }

        try {
            $approveAction->handle($coordinator, $instance, [], 'approved', 'approve');
            $this->fail('Expected generic approve to fail closed for RES-041.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not explicitly configured', $exception->getMessage());
        }

        // Instructor can endorse
        $endorsedInstance = $approveAction->handle($instructor, $instance, ['notes' => 'Instructor endorse'], 'endorsed', 'endorse');
        $this->assertSame('endorsed', $endorsedInstance->status);

        // Coordinator can receive
        $receivedInstance = $approveAction->handle($coordinator, $instance, ['notes' => 'Coordinator receive'], 'approved', 'receive');
        $this->assertSame('approved', $receivedInstance->status);

        // Coordinator CANNOT perform instructor endorsement on new draft instance
        $instance2 = $createAction->handle($instructor, 'RES-041', classId: $class->id);
        $assignAction->handle($class->facilitator, $instance2, $coordinator->id, 'program_coordinator');

        $this->expectException(InvalidArgumentException::class);
        $approveAction->handle($coordinator, $instance2, ['notes' => 'Coordinator endorse attempt'], 'endorsed', 'endorse');
    }

    public function test_res040_rejects_generic_approve_and_out_of_order_receive(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-040.receive');
        $group = $this->createGroup(adviser: $adviser);

        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-040', $group->id);
        (new AssignOfficialFormActor)->handle($group->creator, $instance, $instructor->id, 'research_instructor');

        $action = new ApproveOfficialForm;

        try {
            $action->handle($adviser, $instance, [], 'approved', 'approve');
            $this->fail('Expected generic approve to fail closed for RES-040.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not explicitly configured', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be performed');
        $action->handle($instructor, $instance, [], 'approved', 'receive');
    }

    public function test_initiated_by_never_grants_res040_endorsement_authority(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-040.endorse', 'forms.res-040.receive');
        $group = $this->createGroup(adviser: $adviser);

        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-040', $group->id);
        $instance->update(['initiated_by' => $instructor->id]);
        (new AssignOfficialFormActor)->handle($group->creator, $instance, $instructor->id, 'research_instructor');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not contextually authorized');
        (new ApproveOfficialForm)->handle($instructor, $instance, [], 'endorsed', 'endorse');
    }

    public function test_class_owned_initiation_requires_the_exact_configured_actor_type(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $languageEditor = User::factory()->create(['user_type' => 'faculty']);
        $languageEditor->givePermissionTo('forms.res-041.fill', 'forms.res-045.certify');
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-041.fill');
        $group = $this->createGroup(adviser: $adviser);
        $class = $group->researchClass;

        $create = new CreateOfficialFormInstance;
        $languageCertificate = $create->handle($group->leader, 'RES-045', $group->id);
        (new AssignOfficialFormActor)->handle($group->creator, $languageCertificate, $languageEditor->id, 'language_editor');

        $definition = OfficialFormDefinition::query()->where('code', 'RES-041')->firstOrFail();
        $authorization = new OfficialFormAuthorization;

        $this->assertFalse($authorization->canInitiate($languageEditor, $definition, class: $class));
        $this->assertFalse($authorization->canInitiate($class->facilitator, $definition, class: $class));

        $instructorEndorsement = $create->handle($adviser, 'RES-040', $group->id);
        (new AssignOfficialFormActor)->handle($group->creator, $instructorEndorsement, $instructor->id, 'research_instructor');

        $this->assertTrue($authorization->canInitiate($instructor, $definition, class: $class));
    }

    public function test_unknown_form_action_fails_closed(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-040', $group->id);

        $adviser->givePermissionTo('forms.res-040.endorse');

        $this->assertFalse((new OfficialFormAuthorization)->canPerformAction($adviser, $instance, 'publish'));
    }

    public function test_custom_role_can_receive_res040_only_with_research_instructor_assignment(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $customRole = Role::create(['name' => 'custom-capstone-receiver', 'guard_name' => 'web']);
        $customRole->givePermissionTo('forms.res-040.receive');
        $receiver = User::factory()->create(['user_type' => 'faculty']);
        $receiver->assignRole($customRole);

        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-040', $group->id);
        $action = new ApproveOfficialForm;
        $action->handle($adviser, $instance, [], 'endorsed', 'endorse');

        try {
            $action->handle($receiver, $instance, [], 'approved', 'receive');
            $this->fail('Expected custom role permission without assignment to be denied.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not contextually authorized', $exception->getMessage());
        }

        (new AssignOfficialFormActor)->handle($group->creator, $instance, $receiver->id, 'research_instructor');
        $received = $action->handle($receiver, $instance, [], 'approved', 'receive');

        $this->assertSame('approved', $received->status);
    }

    public function test_custom_role_compatibility_with_actor_assignment(): void
    {
        // Create custom role without canonical name
        $customRole = Role::create(['name' => 'custom-language-reviewer', 'guard_name' => 'web']);
        $customRole->givePermissionTo('forms.res-045.certify');

        $reviewer = User::factory()->create(['user_type' => 'faculty']);
        $reviewer->assignRole($customRole);

        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($group->leader, 'RES-045', $group->id);

        $certifyAction = new CertifyOfficialForm;

        // Without actor assignment -> DENIED
        try {
            $certifyAction->handle($reviewer, $instance, ['notes' => 'Attempt without assignment']);
            $this->fail('Expected InvalidArgumentException without assignment.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not contextually authorized', $e->getMessage());
        }

        // With language_editor assignment -> ALLOWED
        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($group->creator, $instance, $reviewer->id, 'language_editor');

        $certified = $certifyAction->handle($reviewer, $instance, ['notes' => 'Approved']);
        $this->assertSame('completed', $certified->status);
    }

    public function test_assign_actor_fails_closed_on_unmapped_form(): void
    {
        $group = $this->createGroup();
        $createAction = new CreateOfficialFormInstance;

        // RES-026 is single_per_group and has no entry in FORM_ALLOWED_ACTOR_TYPES
        $instance = $createAction->handle($group->leader, 'RES-026', $group->id);

        $assignAction = new AssignOfficialFormActor;
        $this->expectException(InvalidArgumentException::class);
        $assignAction->handle($group->creator, $instance, $group->creator->id, 'consultant');
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
                initiator: $group->leader,
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

        // 4. Group Leader initiating RES-043A for assigned Validator A linked to RES-042 succeeds
        $res043a = $action->handle(
            initiator: $group->leader,
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
            initiator: $group->leader,
            formCode: 'RES-043A',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $res042->id,
            actorUserId: $validatorB->id
        );
    }
}

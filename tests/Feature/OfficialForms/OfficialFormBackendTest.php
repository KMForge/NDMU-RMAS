<?php

namespace Tests\Feature\OfficialForms;

use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\Document;
use App\Models\DocumentReview;
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
        $facilitator->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit', 'forms.res-033.endorse', 'forms.res-045.certify', 'forms.res-036.evaluate', 'forms.res-047.endorse');

        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $leaderUser = $leader ?? User::factory()->create(['user_type' => 'student']);
        $leaderUser->givePermissionTo(
            'forms.res-026.fill', 'forms.res-026.submit', 'forms.res-026.view',
            'forms.res-031.fill', 'forms.res-031.view',
            'forms.res-033.endorse', 'forms.res-045.certify', 'forms.res-046.certify',
            'forms.res-042.submit', 'forms.res-040.endorse',
            'forms.res-041.fill', 'forms.res-041.endorse',
            'forms.res-043a.validate', 'forms.res-039.fill',
            'forms.res-047.endorse',
            'forms.res-048.fill', 'forms.res-049.sign'
        );

        if ($adviser !== null) {
            $adviser->givePermissionTo('forms.res-033.endorse', 'forms.res-026.approve', 'forms.res-026.view', 'forms.res-040.endorse');
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
            payload: ['date' => '2026-08-13', 'topics' => ['AI Title 1', 'AI Title 2']]
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame('draft', $instance->status);
        $this->assertSame(1, $instance->currentVersion->version_number);
        $this->assertSame(['AI Title 1', 'AI Title 2'], $instance->currentVersion->payload['topics']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.created',
            'auditable_id' => $instance->id,
        ]);
    }

    public function test_res026_payload_whitelisting_rejects_forbidden_system_keys(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit');
        $group = $this->createGroup(leader: $student);

        $action = new CreateOfficialFormInstance;
        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $student,
            formCode: 'RES-026',
            groupId: $group->id,
            payload: ['status' => 'approved', 'topics' => ['Title 1']]
        );
    }

    public function test_res026_full_lifecycle_create_submit_approve_and_print(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(leader: $student, adviser: $adviser);
        $student->update(['research_class_group_id' => $group->id]);

        // 1. Create draft
        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle(
            initiator: $student,
            formCode: 'RES-026',
            groupId: $group->id,
            payload: ['date' => '2026-08-13', 'topics' => ['Title A', 'Title B', 'Title C']]
        );

        // 2. Submit v1
        $submitAction = new SubmitOfficialFormVersion;
        $version = $submitAction->handle(
            actor: $student,
            instance: $instance,
            payload: ['date' => '2026-08-13', 'topics' => ['Title A Updated', 'Title B', 'Title C']],
            nextStatus: 'submitted'
        );

        $this->assertSame(2, $version->version_number);
        $this->assertSame('submitted', $instance->fresh()->status);

        // 3. Approve form
        $approveAction = new ApproveOfficialForm;
        $approvedInstance = $approveAction->handle(
            approver: $adviser,
            instance: $instance->fresh(),
            approvalMetadata: ['approved_title_number' => 1, 'remarks' => 'Approved title 1.'],
            targetStatus: 'approved'
        );

        $this->assertSame('approved', $approvedInstance->status);

        // 4. Authorized user prints form
        $response = $this->actingAs($student)->get(route('official-forms.print', $instance->id));
        $response->assertStatus(200);
        $response->assertSee('NOTRE DAME OF MARBEL UNIVERSITY');
        $response->assertSee('APPROVED');

        // 5. Unauthorized user from another group is denied print access
        $otherStudent = User::factory()->create(['user_type' => 'student']);
        $otherStudent->givePermissionTo('forms.res-026.view');
        $this->actingAs($otherStudent)
            ->get(route('official-forms.print', $instance->id))
            ->assertStatus(403);
    }

    public function test_source_whitelist_prevents_unauthorized_source_types(): void
    {
        $group = $this->createGroup();
        $action = new CreateOfficialFormInstance;

        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $group->leader,
            formCode: 'RES-031',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: 999
        );
    }

    public function test_res031_consultation_record_source_linkage(): void
    {
        $group = $this->createGroup();
        $request = ConsultationRequest::query()->create([
            'research_class_group_id' => $group->id,
            'requested_by' => $group->leader->id,
            'adviser_id' => $group->creator->id,
            'request_token' => (string) Str::uuid(),
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_at' => now()->addDays(2),
            'consultation_mode' => 'in_person',
            'agenda' => 'Discuss research methodology',
            'status' => 'approved',
        ]);

        $record = ConsultationRecord::query()->create([
            'consultation_request_id' => $request->id,
            'research_class_group_id' => $group->id,
            'conducted_by' => $group->creator->id,
            'consultation_date' => now()->toDateString(),
            'consulted_at' => now(),
            'consultation_mode' => 'in_person',
            'agenda' => 'Discuss research methodology',
            'discussion' => 'Discussed methodology in detail',
            'notes' => 'Discussed methodology',
            'status' => 'completed',
        ]);

        $action = new CreateOfficialFormInstance;
        $instance = $action->handle(
            initiator: $group->leader,
            formCode: 'RES-031',
            groupId: $group->id,
            sourceType: ConsultationRecord::class,
            sourceId: $record->id,
            payload: ['remarks' => 'Valid consultation']
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame(ConsultationRecord::class, $instance->source_type);
        $this->assertSame($record->id, $instance->source_id);
    }

    public function test_res039_document_review_source_linkage(): void
    {
        $group = $this->createGroup();
        $doc = Document::query()->create([
            'research_class_group_id' => $group->id,
            'user_id' => $group->leader->id,
            'uploaded_by' => $group->leader->id,
            'title' => 'Proposal Manuscript',
            'storage_path' => 'documents/test.pdf',
            'original_filename' => 'test.pdf',
            'stored_filename' => 'test.pdf',
            'file_hash' => 'hash123',
            'content_sha256' => hash('sha256', 'test'),
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'file_type' => 'proposal_manuscript',
            'storage_disk' => 'private',
            'submission_token' => (string) Str::uuid(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $review = DocumentReview::query()->create([
            'document_id' => $doc->id,
            'research_class_group_id' => $group->id,
            'reviewer_id' => $group->creator->id,
            'decision' => 'accepted',
            'reviewed_at' => now(),
            'status' => 'completed',
        ]);

        $action = new CreateOfficialFormInstance;
        $instance = $action->handle(
            initiator: $group->leader,
            formCode: 'RES-039',
            groupId: $group->id,
            sourceType: DocumentReview::class,
            sourceId: $review->id,
            payload: ['general_notes' => 'Revision matrix created']
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame(DocumentReview::class, $instance->source_type);
        $this->assertSame($review->id, $instance->source_id);
    }

    public function test_res047_reproduction_endorsement_flow(): void
    {
        $group = $this->createGroup();
        $facilitator = $group->creator;

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle(
            initiator: $group->leader,
            formCode: 'RES-047',
            groupId: $group->id,
            payload: ['manuscript_title' => 'Final Approved Capstone', 'copies_authorized' => 5]
        );

        $approveAction = new ApproveOfficialForm;
        $endorsedInstance = $approveAction->handle(
            approver: $facilitator,
            instance: $instance,
            approvalMetadata: ['copies' => 5],
            targetStatus: 'endorsed'
        );

        $this->assertSame('endorsed', $endorsedInstance->status);
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
        $endorsedInstance = $approveAction->handle($adviser, $instance1, ['notes' => 'Endorsed'], 'endorsed');
        $this->assertSame('endorsed', $endorsedInstance->status);

        // Research Instructor can receive
        $receivedInstance = $approveAction->handle($instructor, $instance1, ['notes' => 'Received'], 'approved');
        $this->assertSame('approved', $receivedInstance->status);

        // Instructor CANNOT perform adviser endorsement on a new draft instance
        $instance2 = $createAction->handle($adviser, 'RES-040', $group->id, contextKey: 'second_submission');
        $assignAction->handle($group->creator, $instance2, $instructor->id, 'research_instructor');

        try {
            $approveAction->handle($instructor, $instance2, ['notes' => 'Attempt'], 'endorsed');
            $this->fail('Expected InvalidArgumentException for instructor endorsement.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not contextually authorized', $e->getMessage());
        }
    }

    public function test_res041_action_specific_actor_rules(): void
    {
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-041.fill', 'forms.res-041.endorse');

        $coordinator = User::factory()->create(['user_type' => 'faculty']);
        $coordinator->givePermissionTo('forms.res-041.receive');

        $group = $this->createGroup();
        $class = $group->researchClass;

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($instructor, 'RES-041', classId: $class->id);

        $assignAction = new AssignOfficialFormActor;
        $assignAction->handle($class->facilitator, $instance, $instructor->id, 'research_instructor');
        $assignAction->handle($class->facilitator, $instance, $coordinator->id, 'program_coordinator');

        $approveAction = new ApproveOfficialForm;

        // Instructor can endorse
        $endorsedInstance = $approveAction->handle($instructor, $instance, ['notes' => 'Instructor endorse'], 'endorsed');
        $this->assertSame('endorsed', $endorsedInstance->status);

        // Coordinator can receive
        $receivedInstance = $approveAction->handle($coordinator, $instance, ['notes' => 'Coordinator receive'], 'approved');
        $this->assertSame('approved', $receivedInstance->status);

        // Coordinator CANNOT perform instructor endorsement on new draft instance
        $instance2 = $createAction->handle($instructor, 'RES-041', classId: $class->id, contextKey: 'second_class_submission');
        $assignAction->handle($class->facilitator, $instance2, $coordinator->id, 'program_coordinator');

        $this->expectException(InvalidArgumentException::class);
        $approveAction->handle($coordinator, $instance2, ['notes' => 'Coordinator endorse attempt'], 'endorsed');
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

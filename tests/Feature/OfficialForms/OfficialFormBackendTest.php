<?php

namespace Tests\Feature\OfficialForms;

use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\Defense;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Models\TitlePresentation;
use App\Models\User;
use App\Modules\OfficialForms\Actions\ApproveOfficialForm;
use App\Modules\OfficialForms\Actions\AssignOfficialFormActor;
use App\Modules\OfficialForms\Actions\AssignResearchClassFormActor;
use App\Modules\OfficialForms\Actions\CertifyOfficialForm;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\DeactivateOfficialFormActor;
use App\Modules\OfficialForms\Actions\DeactivateResearchClassFormActor;
use App\Modules\OfficialForms\Actions\SaveOfficialFormDraft;
use App\Modules\OfficialForms\Actions\SubmitOfficialFormVersion;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Actions\TransitionOfficialForm;
use App\Modules\OfficialForms\Services\NotifyNextRequiredOfficialForms;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Validators\OfficialFormPayloadValidator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionMethod;
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
            $adviser->givePermissionTo('forms.res-031.sign', 'forms.res-033.endorse', 'forms.res-026.view', 'forms.res-040.endorse');
        }

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'name' => 'Group '.bin2hex(random_bytes(3)),
            'leader_student_id' => $leaderUser->id,
            'adviser_id' => $adviser?->id,
            'created_by' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'status' => 'active',
        ]);

        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $class->id,
            'student_id' => $leaderUser->id,
            'status' => 'active',
            'requested_at' => now()->subDay(),
            'joined_at' => now(),
            'reviewed_by' => $facilitator->id,
            'reviewed_at' => now(),
        ]);

        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $class->id,
            'research_class_enrollment_id' => $enrollment->id,
            'student_id' => $leaderUser->id,
            'assigned_by' => $facilitator->id,
        ]);

        Document::query()->forceCreate([
            'user_id' => $leaderUser->id,
            'research_class_group_id' => $group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'approved-title-proposal.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => 'title_proposal',
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => "documents/test/{$group->id}/approved-title-proposal.pdf",
            'content_sha256' => hash('sha256', $group->id.'-title-proposal'),
            'submitted_at' => now(),
            'status' => 'approved_for_presentation',
        ]);

        return $group;
    }

    private function finalizeTitleApproval(ResearchClassGroup $group): OfficialFormInstance
    {
        $instance = (new CreateOfficialFormInstance)->handle(
            initiator: $group->leader,
            formCode: 'RES-026',
            groupId: $group->id,
            payload: ['date' => '2026-08-29', 'topics' => ['Title A', 'Title B', 'Title C']],
        );
        $instance->update(['status' => 'approved']);

        $defense = Defense::query()->create([
            'research_class_group_id' => $group->id,
            'defense_type' => 'title_presentation',
            'status' => 'completed',
            'created_by' => $group->created_by,
        ]);

        TitlePresentation::query()->create([
            'defense_id' => $defense->id,
            'official_form_instance_id' => $instance->id,
            'official_form_version_id' => $instance->current_version_id,
            'status' => 'finalized',
            'approved_title_number' => 1,
            'finalized_at' => now(),
            'finalized_by' => $group->created_by,
        ]);

        return $instance->fresh();
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
            'event' => 'RES026_CREATED',
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

    public function test_res026_can_be_submitted_and_printed_but_has_no_unverified_adviser_approval_workflow(): void
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

        // RES-026 has institutional signature lines, but the current template does
        // not establish an adviser approval action.
        $approveAction = new ApproveOfficialForm;
        try {
            $approveAction->handle($adviser, $instance->fresh(), [], 'approved', 'approve');
            $this->fail('Expected RES-026 adviser approval to fail closed.');
        } catch (InvalidArgumentException $exception) {
            $this->assertTrue(
                str_contains($exception->getMessage(), 'not explicitly configured')
                    || str_contains($exception->getMessage(), 'not contextually authorized')
                    || str_contains($exception->getMessage(), 'cannot transition')
                    || str_contains($exception->getMessage(), 'cannot be performed')
            );
        }

        // 4. Authorized user prints form
        $response = $this->actingAs($student)->get(route('official-forms.print', $instance->id));
        $response->assertStatus(200);
        $response->assertSee('NOTRE DAME OF MARBEL UNIVERSITY');
        $response->assertSee('SUBMITTED');

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
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $this->finalizeTitleApproval($group);
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
            initiator: $adviser,
            formCode: 'RES-031',
            groupId: $group->id,
            sourceType: ConsultationRecord::class,
            sourceId: $record->id,
            payload: []
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame(ConsultationRecord::class, $instance->source_type);
        $this->assertSame($record->id, $instance->source_id);
    }

    public function test_res031_is_repeatably_available_to_assigned_adviser_after_final_title_approval(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);

        try {
            (new CreateOfficialFormInstance)->handle($adviser, 'RES-031', $group->id);
            $this->fail('RES-031 must remain locked before final title approval.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Title Presentation is finalized', $exception->getMessage());
        }

        $this->finalizeTitleApproval($group);

        $first = (new CreateOfficialFormInstance)->handle($adviser, 'RES-031', $group->id);
        $second = (new CreateOfficialFormInstance)->handle($adviser, 'RES-031', $group->id);

        $this->assertNotSame($first->id, $second->id);
        $this->assertNull($first->source_type);
        $this->assertNull($first->source_id);
    }

    public function test_res031_accepts_legacy_res026_presentations_saved_as_approved(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $res026 = $this->finalizeTitleApproval($group);
        $res026->titlePresentation()->update(['status' => 'approved']);

        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-031', $group->id);

        $this->assertSame('RES-031', $instance->definition->code);
    }

    public function test_student_cannot_initiate_res031_after_final_title_approval(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $this->finalizeTitleApproval($group);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not authorized to initiate form RES-031');

        (new CreateOfficialFormInstance)->handle($group->leader, 'RES-031', $group->id);
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
            payload: ['revisions' => [['area' => 'Methodology', 'suggestions' => 'Clarify sampling']]]
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame(DocumentReview::class, $instance->source_type);
        $this->assertSame($review->id, $instance->source_id);
    }

    public function test_res047_reproduction_endorsement_flow(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $adviser->givePermissionTo('forms.res-047.view', 'forms.res-047.endorse');
        $dean = User::factory()->create(['user_type' => 'faculty']);
        $dean->givePermissionTo('forms.res-047.approve');
        $group = $this->createGroup(adviser: $adviser);
        (new AssignResearchClassFormActor)->handle($group->creator, $group->researchClass, $dean, 'dean');

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle(
            initiator: $adviser,
            formCode: 'RES-047',
            groupId: $group->id,
            payload: ['date' => '2026-08-13', 'salutation' => 'Dear College Dean']
        );

        $approveAction = new ApproveOfficialForm;
        $endorsedInstance = $approveAction->handle(
            approver: $adviser,
            instance: $instance,
            approvalMetadata: [],
            targetStatus: 'endorsed',
            action: 'endorse'
        );

        $this->assertSame('endorsed', $endorsedInstance->status);
        $approved = $approveAction->handle($dean, $endorsedInstance, [], 'approved', 'approve');
        $this->assertSame('approved', $approved->status);
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
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-041.fill', 'forms.res-041.endorse', 'forms.res-041.receive');

        $coordinator = User::factory()->create(['user_type' => 'faculty']);
        $coordinator->givePermissionTo('forms.res-041.receive');

        $group = $this->createGroup();
        $class = $group->researchClass;

        $createAction = new CreateOfficialFormInstance;
        $classAssignAction = new AssignResearchClassFormActor;
        $classAssignAction->handle($class->facilitator, $class, $instructor, 'research_instructor');
        $classAssignAction->handle($class->facilitator, $class, $coordinator, 'program_coordinator');
        $instance = $createAction->handle($instructor, 'RES-041', classId: $class->id);

        $approveAction = new ApproveOfficialForm;

        // Instructor can endorse
        $endorsedInstance = $approveAction->handle($instructor, $instance, ['notes' => 'Instructor endorse'], 'endorsed', 'endorse');
        $this->assertSame('endorsed', $endorsedInstance->status);

        // Coordinator can receive
        $receivedInstance = $approveAction->handle($coordinator, $instance, ['notes' => 'Coordinator receive'], 'approved', 'receive');
        $this->assertSame('approved', $receivedInstance->status);

        // Coordinator CANNOT perform instructor endorsement on new draft instance
        $instance2 = $createAction->handle($instructor, 'RES-041', classId: $class->id, contextKey: 'second_class_submission');
        $this->expectException(InvalidArgumentException::class);
        $approveAction->handle($coordinator, $instance2, ['notes' => 'Coordinator endorse attempt'], 'endorsed', 'endorse');
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

    public function test_res036_requires_authoritative_defense_schedule_source(): void
    {
        $panelist = User::factory()->create(['user_type' => 'faculty']);
        $panelist->givePermissionTo('forms.res-036.evaluate');
        $group = $this->createGroup();

        $action = new CreateOfficialFormInstance;

        try {
            $action->handle($panelist, 'RES-036', $group->id, actorUserId: $panelist->id);
            $this->fail('Expected InvalidArgumentException for RES-036 creation.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Form RES-036 requires its configured authoritative source', $e->getMessage());
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

    public function test_group_and_class_ownership_are_mutually_exclusive(): void
    {
        $group = $this->createGroup();
        $groupInstance = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-026', $group->id);

        $this->assertSame($group->id, $groupInstance->research_class_group_id);
        $this->assertNull($groupInstance->research_class_id);

        try {
            (new CreateOfficialFormInstance)->handle(
                $group->leader,
                'RES-030',
                groupId: $group->id,
                classId: $group->research_class_id
            );
            $this->fail('Expected mixed ownership to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('must not specify a research_class_id', $exception->getMessage());
        }

        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-041.fill');
        (new AssignResearchClassFormActor)->handle($group->creator, $group->researchClass, $instructor, 'research_instructor');
        $classInstance = (new CreateOfficialFormInstance)->handle($instructor, 'RES-041', classId: $group->research_class_id);

        $this->assertSame($group->research_class_id, $classInstance->research_class_id);
        $this->assertNull($classInstance->research_class_group_id);
    }

    public function test_res041_permission_alone_and_cross_class_assignment_do_not_authorize_initiation(): void
    {
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo('forms.res-041.fill', 'forms.res-041.endorse', 'forms.res-041.receive');
        $firstGroup = $this->createGroup();
        $secondGroup = $this->createGroup();

        $action = new CreateOfficialFormInstance;
        try {
            $action->handle($instructor, 'RES-041', classId: $firstGroup->research_class_id);
            $this->fail('Permission alone must not authorize RES-041 initiation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not authorized to initiate', $exception->getMessage());
        }

        (new AssignResearchClassFormActor)->handle(
            $firstGroup->creator,
            $firstGroup->researchClass,
            $instructor,
            'program_coordinator'
        );
        try {
            $action->handle($instructor, 'RES-041', classId: $firstGroup->research_class_id);
            $this->fail('A wrong same-class actor type must not authorize RES-041 initiation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not authorized to initiate', $exception->getMessage());
        }

        (new AssignResearchClassFormActor)->handle(
            $firstGroup->creator,
            $firstGroup->researchClass,
            $instructor,
            'research_instructor'
        );

        $this->expectException(InvalidArgumentException::class);
        $action->handle($instructor, 'RES-041', classId: $secondGroup->research_class_id);
    }

    public function test_approval_action_is_required_and_target_status_cannot_choose_it(): void
    {
        $method = new ReflectionMethod(ApproveOfficialForm::class, 'handle');
        $this->assertSame(5, $method->getNumberOfRequiredParameters());

        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $adviser->givePermissionTo('forms.res-040.endorse');
        $group = $this->createGroup(adviser: $adviser);
        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-040', $group->id);

        $this->expectException(InvalidArgumentException::class);
        (new ApproveOfficialForm)->handle($adviser, $instance, [], 'approved', 'endorse');
    }

    public function test_res026_rejects_institutional_decision_and_actor_payload_fields(): void
    {
        $group = $this->createGroup();

        foreach (['approved_title_number', 'remarks', 'panel_names', 'students'] as $forbiddenField) {
            try {
                (new CreateOfficialFormInstance)->handle(
                    $group->leader,
                    'RES-026',
                    $group->id,
                    payload: [$forbiddenField => 'spoofed']
                );
                $this->fail("Expected {$forbiddenField} to be rejected.");
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('unknown fields', $exception->getMessage());
            }
        }
    }

    public function test_res047_facilitator_cannot_substitute_for_adviser_or_dean(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $adviser->givePermissionTo('forms.res-047.view', 'forms.res-047.endorse');
        $group = $this->createGroup(adviser: $adviser);
        $instance = (new CreateOfficialFormInstance)->handle($adviser, 'RES-047', $group->id);
        $facilitator = $group->creator;

        $this->expectException(InvalidArgumentException::class);
        (new ApproveOfficialForm)->handle($facilitator, $instance, [], 'endorsed', 'endorse');
    }

    public function test_res043_source_must_belong_to_the_same_group(): void
    {
        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');
        $sourceGroup = $this->createGroup();
        $otherGroup = $this->createGroup();
        $source = (new CreateOfficialFormInstance)->handle($sourceGroup->leader, 'RES-042', $sourceGroup->id);
        (new AssignOfficialFormActor)->handle($sourceGroup->creator, $source, $validator->id, 'instrument_validator');

        $this->expectException(InvalidArgumentException::class);
        (new CreateOfficialFormInstance)->handle(
            $otherGroup->leader,
            'RES-043A',
            groupId: $otherGroup->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $source->id,
            actorUserId: $validator->id
        );
    }

    public function test_saving_a_draft_creates_an_immutable_linear_version_history(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit');
        $group = $this->createGroup(leader: $student);
        $instance = (new CreateOfficialFormInstance)->handle(
            $student,
            'RES-026',
            $group->id,
            payload: ['date' => '2026-08-13', 'topics' => ['Version one']],
        );

        $first = $instance->currentVersion;
        $second = (new SaveOfficialFormDraft)->handle($student, $instance, [
            'date' => '2026-08-14',
            'topics' => ['Version two'],
        ]);
        $third = (new SaveOfficialFormDraft)->handle($student, $instance->fresh(), [
            'date' => '2026-08-15',
            'topics' => ['Version three'],
        ]);

        $this->assertSame(1, $first->version_number);
        $this->assertSame(['Version one'], $first->fresh()->payload['topics']);
        $this->assertSame($first->id, $second->supersedes_version_id);
        $this->assertSame($second->id, $third->supersedes_version_id);
        $this->assertSame(3, $third->version_number);
        $this->assertSame(1, $instance->versions()->where('is_current', true)->count());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.draft_saved',
            'auditable_id' => $instance->id,
        ]);
    }

    public function test_terminal_form_cannot_be_silently_edited(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-026.fill', 'forms.res-026.submit');
        $group = $this->createGroup(leader: $student);
        $instance = (new CreateOfficialFormInstance)->handle($student, 'RES-026', $group->id);
        $instance->update(['status' => 'approved']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not editable from status approved');

        (new SaveOfficialFormDraft)->handle($student, $instance, ['topics' => ['Changed title']]);
    }

    public function test_class_actor_assignment_replaces_the_active_actor_and_is_audited(): void
    {
        $group = $this->createGroup();
        $class = $group->researchClass;
        $first = User::factory()->create(['user_type' => 'faculty']);
        $second = User::factory()->create(['user_type' => 'faculty']);
        $first->givePermissionTo('forms.res-041.fill');
        $second->givePermissionTo('forms.res-041.endorse');

        $action = new AssignResearchClassFormActor;
        $oldAssignment = $action->handle($group->creator, $class, $first, 'research_instructor');
        $newAssignment = $action->handle($group->creator, $class, $second, 'research_instructor');

        $this->assertSame('inactive', $oldAssignment->fresh()->status);
        $this->assertSame('active', $newAssignment->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.class_actor_assigned',
            'auditable_id' => $class->id,
        ]);

        (new DeactivateResearchClassFormActor)->handle($group->creator, $class, $newAssignment);
        $this->assertSame('inactive', $newAssignment->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.class_actor_deactivated',
            'auditable_id' => $class->id,
        ]);
    }

    public function test_class_actor_assignment_denies_wrong_facilitator_and_student_target(): void
    {
        $group = $this->createGroup();
        $outsider = User::factory()->create(['user_type' => 'faculty']);
        $candidate = User::factory()->create(['user_type' => 'faculty']);
        $candidate->givePermissionTo('forms.res-041.fill');

        try {
            (new AssignResearchClassFormActor)->handle(
                $outsider,
                $group->researchClass,
                $candidate,
                'research_instructor',
            );
            $this->fail('A facilitator must not manage another facilitator\'s class actor assignments.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('cannot assign institutional actors', $exception->getMessage());
        }

        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-041.fill');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not eligible for class actor type');
        (new AssignResearchClassFormActor)->handle(
            $group->creator,
            $group->researchClass,
            $student,
            'research_instructor',
        );
    }

    public function test_res043b_is_bound_to_its_res042_validator_and_can_be_validated(): void
    {
        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate', 'forms.res-043b.validate');
        $group = $this->createGroup();
        $source = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);
        (new AssignOfficialFormActor)->handle($group->creator, $source, $validator->id, 'instrument_validator');

        $instance = (new CreateOfficialFormInstance)->handle(
            $validator,
            'RES-043B',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $source->id,
            actorUserId: $validator->id,
        );
        (new SaveOfficialFormDraft)->handle($validator, $instance, [
            'ratings' => [5, 4, 5, 4, 5],
            'date' => '2026-08-13',
        ]);
        $completed = (new ApproveOfficialForm)->handle($validator, $instance->fresh(), [], 'completed', 'validate');

        $this->assertSame('completed', $completed->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.validated',
            'auditable_id' => $instance->id,
        ]);
    }

    public function test_admin_can_create_a_specialist_shell_but_only_assigned_editor_can_certify_it(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->givePermissionTo('users.manage', 'forms.res-046.certify');
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $editor->givePermissionTo('forms.res-046.certify');
        $group = $this->createGroup();

        $instance = (new CreateOfficialFormInstance)->handle($admin, 'RES-046', $group->id);
        $assignment = (new AssignOfficialFormActor)->handle(
            $admin,
            $instance,
            $editor->id,
            'technical_editor',
        );
        $completed = (new CertifyOfficialForm)->handle($editor, $instance, ['notes' => 'Technical editing complete.']);

        $this->assertSame('completed', $completed->status);

        (new DeactivateOfficialFormActor)->handle($admin, $instance, $assignment);
        $this->assertSame('inactive', $assignment->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.actor_deactivated',
            'auditable_id' => $instance->id,
        ]);
    }

    public function test_unrelated_class_facilitator_cannot_assign_validator_to_res_042(): void
    {
        $group = $this->createGroup();
        $res042 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);

        $unrelatedFacilitator = User::factory()->create(['user_type' => 'faculty']);
        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not authorized to assign actors');

        (new AssignOfficialFormActor)->handle($unrelatedFacilitator, $res042, $validator->id, 'instrument_validator');
    }

    public function test_exact_group_adviser_can_assign_eligible_validator_to_res_042(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $res042 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);

        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');

        $assignment = (new AssignOfficialFormActor)->handle($adviser, $res042, $validator->id, 'instrument_validator');
        $this->assertSame($validator->id, $assignment->user_id);
        $this->assertSame('active', $assignment->status);
    }

    public function test_group_created_by_user_can_assign_validator_following_backend_rules(): void
    {
        $group = $this->createGroup();
        $creator = $group->creator; // created_by user
        $res042 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);

        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043b.validate');

        $assignment = (new AssignOfficialFormActor)->handle($creator, $res042, $validator->id, 'instrument_validator');
        $this->assertSame($validator->id, $assignment->user_id);
        $this->assertSame('active', $assignment->status);
    }

    public function test_unrelated_faculty_cannot_assign_validator_to_res_042(): void
    {
        $group = $this->createGroup();
        $res042 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);

        $unrelatedFaculty = User::factory()->create(['user_type' => 'faculty']);
        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not authorized to assign actors');

        (new AssignOfficialFormActor)->handle($unrelatedFaculty, $res042, $validator->id, 'instrument_validator');
    }

    public function test_student_cannot_assign_validator_to_res_042(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup(leader: $student);
        $res042 = (new CreateOfficialFormInstance)->handle($student, 'RES-042', $group->id);

        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not authorized to assign actors');

        (new AssignOfficialFormActor)->handle($student, $res042, $validator->id, 'instrument_validator');
    }

    public function test_system_admin_can_assign_validator_administratively_without_becoming_academic_validator(): void
    {
        $admin = User::factory()->create(['user_type' => 'faculty']);
        $admin->givePermissionTo('users.manage');

        $group = $this->createGroup();
        $res042 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);

        $validator = User::factory()->create(['user_type' => 'faculty']);
        $validator->givePermissionTo('forms.res-043a.validate');

        $assignment = (new AssignOfficialFormActor)->handle($admin, $res042, $validator->id, 'instrument_validator');
        $this->assertSame($validator->id, $assignment->user_id);
        $this->assertNotEquals($admin->id, $assignment->user_id);
    }

    public function test_res_029_authoritative_template_renders_and_accepts_only_verified_payload_fields(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup(leader: $student);
        $student->givePermissionTo('forms.res-029.respond', 'forms.res-029.view');

        $instance = (new CreateOfficialFormInstance)->handle(
            $student,
            'RES-029',
            $group->id,
            payload: [
                'date' => '2026-08-28',
                'course' => 'BS Computer Science',
                'research_title' => 'Secure Research Management',
            ],
        );

        $response = $this->actingAs($student)->get(route('official-forms.workspace.show', $instance));
        $response->assertStatus(200);
        $response->assertSee('Invitation to Research Language Editor');
        $response->assertSee('BS Computer Science');
        $response->assertSee('Secure Research Management');
        $response->assertDontSee('Template Under Verification');

        $printResponse = $this->actingAs($student)->get(route('official-forms.print', $instance));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Invitation to Research Language Editor');
        $printResponse->assertSee('Certification of Language Editing');
        $printResponse->assertDontSee('Institutional print view template under verification');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload contains unknown fields');
        (new CreateOfficialFormInstance)->handle(
            $student,
            'RES-029',
            $group->id,
            contextKey: 'invalid-fields',
            payload: ['assignment_effect' => 'replace_existing_editor'],
        );
    }

    public function test_res031_workspace_and_print_renders_authoritative_consultation_record_data(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);
        $this->finalizeTitleApproval($group);
        $request = ConsultationRequest::query()->create([
            'research_class_group_id' => $group->id,
            'requested_by' => $group->leader->id,
            'adviser_id' => $group->creator->id,
            'request_token' => (string) Str::uuid(),
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_at' => now()->addDays(2),
            'consultation_mode' => 'in_person',
            'agenda' => 'Methodology & Instruments',
            'status' => 'approved',
        ]);

        $record = ConsultationRecord::query()->create([
            'consultation_request_id' => $request->id,
            'research_class_group_id' => $group->id,
            'conducted_by' => $group->creator->id,
            'consultation_date' => now()->toDateString(),
            'consulted_at' => now(),
            'consultation_mode' => 'in_person',
            'agenda' => 'Validate survey questionnaire',
            'discussion' => 'Reviewed Likert scale items with adviser',
            'recommendations' => 'Revise item 4 for clarity',
            'status' => 'completed',
        ]);

        $instance = (new CreateOfficialFormInstance)->handle(
            initiator: $adviser,
            formCode: 'RES-031',
            groupId: $group->id,
            sourceType: ConsultationRecord::class,
            sourceId: $record->id,
            payload: []
        );

        $showResponse = $this->actingAs($group->leader)->get(route('official-forms.workspace.show', $instance));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Validate survey questionnaire');
        $showResponse->assertSee('Reviewed Likert scale items with adviser');
        $showResponse->assertSee('Revise item 4 for clarity');

        $printResponse = $this->actingAs($group->leader)->get(route('official-forms.print', $instance));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Validate survey questionnaire');
        $printResponse->assertSee('Reviewed Likert scale items with adviser');
    }

    public function test_res039_revision_request_source_linkage_rendering_and_print(): void
    {
        $group = $this->createGroup();
        $doc = Document::query()->create([
            'research_class_group_id' => $group->id,
            'user_id' => $group->leader->id,
            'uploaded_by' => $group->leader->id,
            'title' => 'Chapter 3 Manuscript',
            'storage_path' => 'documents/ch3.pdf',
            'original_filename' => 'ch3.pdf',
            'stored_filename' => 'ch3.pdf',
            'file_hash' => 'hash456',
            'content_sha256' => hash('sha256', 'ch3'),
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'file_type' => 'proposal_manuscript',
            'storage_disk' => 'private',
            'submission_token' => (string) Str::uuid(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $revRequest = RevisionRequest::query()->create([
            'research_class_group_id' => $group->id,
            'document_id' => $doc->id,
            'requested_by' => $group->creator->id,
            'source_type' => 'adviser',
            'title' => 'Sampling Technique Clarification',
            'instructions' => 'Explain stratified sampling procedure in Chapter 3',
            'status' => 'open',
        ]);

        $instance = (new CreateOfficialFormInstance)->handle(
            initiator: $group->leader,
            formCode: 'RES-039',
            groupId: $group->id,
            sourceType: RevisionRequest::class,
            sourceId: $revRequest->id,
            payload: ['revisions' => ['method' => ['suggestions' => 'Explain stratified sampling', 'revision_made' => 'Added Section 3.2', 'pages' => '45-47']], 'recommendation' => 'proposal', 'date' => '2026-08-14']
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertSame(RevisionRequest::class, $instance->source_type);
        $this->assertSame($revRequest->id, $instance->source_id);

        $showResponse = $this->actingAs($group->leader)->get(route('official-forms.workspace.show', $instance));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Sampling Technique Clarification');
        $showResponse->assertSee('Explain stratified sampling procedure in Chapter 3');

        $printResponse = $this->actingAs($group->leader)->get(route('official-forms.print', $instance));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Sampling Technique Clarification');
    }

    public function test_res042_instrument_validator_assignment_security_matrix(): void
    {
        $group = $this->createGroup();
        $res042ReqA = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);
        $res042ReqB = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id, contextKey: 'req_b');

        $validatorCandidate = User::factory()->create(['user_type' => 'faculty']);
        $validatorCandidate->givePermissionTo('forms.res-043a.validate');

        // 1. Ineligible candidate without permission fails assignment
        $unqualifiedFaculty = User::factory()->create(['user_type' => 'faculty']);
        $this->expectException(InvalidArgumentException::class);
        (new AssignOfficialFormActor)->handle($group->creator, $res042ReqA, $unqualifiedFaculty->id, 'instrument_validator');
    }

    public function test_res042_validator_assignment_isolation_and_deactivation(): void
    {
        $group = $this->createGroup();
        $res042ReqA = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id);
        $res042ReqB = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-042', $group->id, contextKey: 'req_b');

        $validatorA = User::factory()->create(['user_type' => 'faculty']);
        $validatorA->givePermissionTo('forms.res-043a.validate');

        // Assign Validator A to Request A
        $assignment = (new AssignOfficialFormActor)->handle($group->creator, $res042ReqA, $validatorA->id, 'instrument_validator');

        // Validator A assignment on Request A CANNOT authorize RES-043A linked to Request B
        $createAction = new CreateOfficialFormInstance;
        try {
            $createAction->handle(
                initiator: $group->leader,
                formCode: 'RES-043A',
                groupId: $group->id,
                sourceType: OfficialFormInstance::class,
                sourceId: $res042ReqB->id,
                actorUserId: $validatorA->id
            );
            $this->fail('Expected assignment A to NOT authorize request B.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('is not an assigned instrument validator for the source RES-042 validation request', $e->getMessage());
        }

        // Deactivating assignment on Request A denies subsequent RES-043A creation on Request A
        (new DeactivateOfficialFormActor)->handle($group->creator, $res042ReqA, $assignment);
        $this->expectException(InvalidArgumentException::class);
        $createAction->handle(
            initiator: $group->leader,
            formCode: 'RES-043A',
            groupId: $group->id,
            sourceType: OfficialFormInstance::class,
            sourceId: $res042ReqA->id,
            actorUserId: $validatorA->id
        );
    }

    public function test_res049_authorship_declaration_phase19_persistence_and_print(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $student->givePermissionTo('forms.res-049.sign', 'forms.res-049.view');
        $group = $this->createGroup(leader: $student);

        $instance = (new CreateOfficialFormInstance)->handle($student, 'RES-049', $group->id);

        $savedVersion = (new SaveOfficialFormDraft)->handle($student, $instance, [
            'authorship_confirmed' => true,
        ]);
        $this->assertTrue($savedVersion->payload['authorship_confirmed']);

        $submitAction = new SubmitOfficialFormVersion;
        $submittedVersion = $submitAction->handle(
            actor: $student,
            instance: $instance,
            payload: ['authorship_confirmed' => true],
            nextStatus: 'submitted'
        );
        $this->assertSame('submitted', $instance->fresh()->status);

        $printResponse = $this->actingAs($student)->get(route('official-forms.print', $instance));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Certificate of Authentic Authorship');

        // IDOR: Unrelated student cannot print RES-049
        $otherStudent = User::factory()->create(['user_type' => 'student']);
        $otherStudent->givePermissionTo('forms.res-049.view');
        $this->actingAs($otherStudent)
            ->get(route('official-forms.print', $instance))
            ->assertStatus(403);
    }

    public function test_res048_is_private_per_evaluator_and_uses_a_frozen_roster_with_server_totals(): void
    {
        $evaluator = User::factory()->create(['user_type' => 'student']);
        $peer = User::factory()->create(['user_type' => 'student']);
        $evaluator->givePermissionTo('forms.res-048.fill', 'forms.res-048.view');
        $peer->givePermissionTo('forms.res-048.fill', 'forms.res-048.view');
        $group = $this->createGroup(leader: $evaluator);

        $peerEnrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $group->research_class_id,
            'student_id' => $peer->id,
            'status' => 'active',
            'requested_at' => now()->subDay(),
            'joined_at' => now(),
            'reviewed_by' => $group->created_by,
            'reviewed_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $group->id,
            'research_class_id' => $group->research_class_id,
            'research_class_enrollment_id' => $peerEnrollment->id,
            'student_id' => $peer->id,
            'assigned_by' => $group->created_by,
        ]);

        $create = new CreateOfficialFormInstance;
        $evaluatorInstance = $create->handle($evaluator, 'RES-048', $group->id);
        $peerInstance = $create->handle($peer, 'RES-048', $group->id);

        $this->assertSame(2, OfficialFormInstance::query()
            ->where('official_form_definition_id', $evaluatorInstance->official_form_definition_id)
            ->where('research_class_group_id', $group->id)
            ->count());
        $this->assertSame($evaluator->id, $evaluatorInstance->currentVersion->source_snapshot['evaluator_user_id']);
        $this->assertSame([$evaluator->id, $peer->id], array_column($evaluatorInstance->currentVersion->source_snapshot['roster'], 'user_id'));

        $this->actingAs($peer)
            ->get(route('official-forms.workspace.show', $evaluatorInstance))
            ->assertForbidden();

        $ratings = array_fill(0, 10, [4, 2]);
        $submitted = (new SubmitOfficialFormVersion)->handle($evaluator, $evaluatorInstance, [
            'evaluation_phase' => 'proposal',
            'evaluation_date' => '2026-08-28',
            'ratings' => $ratings,
        ]);

        $this->assertSame([40, 20], $submitted->payload['totals']);
        $this->assertSame($evaluator->id, $submitted->source_snapshot['evaluator_user_id']);
        $this->assertSame('submitted', $evaluatorInstance->fresh()->status);

        $this->expectException(InvalidArgumentException::class);
        (new SubmitOfficialFormVersion)->handle($peer, $peerInstance, [
            'evaluation_phase' => 'final',
            'evaluation_date' => '2026-08-28',
            'ratings' => array_fill(0, 10, [5, 1]),
        ]);
    }

    public function test_res_029_conforme_activates_language_editor_assignment_for_res_045(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $editor->givePermissionTo('forms.res-029.respond', 'forms.res-045.certify');
        $group = $this->createGroup();
        $group->leader->givePermissionTo('forms.res-029.respond', 'forms.res-045.certify');

        $res029 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-029', $group->id);
        (new AssignOfficialFormActor)->handle($group->creator, $res029, $editor->id, 'language_editor');

        // Execute transition to conformed
        (new TransitionOfficialForm)->handle($editor, $res029, 'respond', 'approved');

        $assignment = OfficialFormActorAssignment::query()
            ->where('official_form_instance_id', $res029->id)
            ->where('user_id', $editor->id)
            ->sole();

        $this->assertSame('active', $assignment->status);

        // Verify editor can certify RES-045 for the group
        $res045 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-045', $group->id);
        $certified = (new TransitionOfficialForm)->handle($editor, $res045, 'certify', 'completed');
        $this->assertSame('completed', $certified->status);
    }

    public function test_res_030_adviser_and_panelist_replacement_preserves_history_and_locks_group(): void
    {
        $oldAdviser = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $newAdviser = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $dean = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $dean->givePermissionTo('forms.res-030.approve', 'forms.res-047.approve', 'dashboards.dean.view');

        $group = $this->createGroup(adviser: $oldAdviser);
        $group->leader->givePermissionTo('forms.res-030.submit');
        (new AssignResearchClassFormActor)->handle($group->creator, $group->researchClass, $dean, 'dean');

        $res030 = (new CreateOfficialFormInstance)->handle(
            $group->leader,
            'RES-030',
            $group->id,
            payload: [
                'date' => '2026-08-28',
                'degree_program' => 'BSCS',
                'research_title' => 'Title',
                'personnel_type' => ['Change of Research Adviser'],
                'current_names' => [$oldAdviser->name],
                'proposed_replacement' => $newAdviser->name,
                'reasons' => 'Schedule conflict',
            ]
        );

        $transitioned = (new TransitionOfficialForm)->handle(
            $dean,
            $res030,
            'approve',
            'approved',
            ['proposed_adviser_id' => $newAdviser->id]
        );

        $this->assertSame('approved', $transitioned->status);
        $this->assertSame($newAdviser->id, $group->fresh()->adviser_id);

        $this->assertDatabaseHas('research_class_group_adviser_histories', [
            'research_class_group_id' => $group->id,
            'adviser_id' => $newAdviser->id,
            'assigned_by' => $dean->id,
        ]);
    }

    public function test_program_head_authority_requires_active_class_actor_assignment(): void
    {
        $programHead = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $programHead->givePermissionTo('forms.res-038.endorse');
        $group = $this->createGroup();
        $group->leader->givePermissionTo('forms.res-038.endorse');

        $res038 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-038', $group->id);

        // Before assignment, endorsement fails
        $authorization = new OfficialFormAuthorization;
        $this->assertFalse($authorization->canPerformAction($programHead, $res038, 'endorse'));

        // Assign as program_head
        (new AssignResearchClassFormActor)->handle($group->creator, $group->researchClass, $programHead, 'program_head');

        $this->assertTrue($authorization->canPerformAction($programHead, $res038, 'endorse'));
    }

    public function test_res_038_requires_program_head_endorsement_and_adviser_conforme(): void
    {
        $programHead = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $programHead->givePermissionTo('forms.res-038.endorse');
        $adviser = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $adviser->givePermissionTo('forms.res-038.endorse');

        $group = $this->createGroup(adviser: $adviser);
        $group->leader->givePermissionTo('forms.res-038.endorse');
        (new AssignResearchClassFormActor)->handle($group->creator, $group->researchClass, $programHead, 'program_head');

        $res038 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-038', $group->id);
        $endorsed = (new TransitionOfficialForm)->handle($programHead, $res038, 'endorse', 'endorsed');

        $this->assertSame('endorsed', $endorsed->status);
        $this->assertSame($adviser->id, $group->fresh()->adviser_id); // Adviser not mutated
    }

    public function test_res_043b_mean_calculated_server_side_and_blocks_res_044_dean_approval(): void
    {
        $validator = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $validator->givePermissionTo('forms.res-043b.validate', 'forms.res-044.endorse');
        $dean = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $dean->givePermissionTo('forms.res-044.endorse', 'forms.res-047.approve');

        $group = $this->createGroup();
        $group->leader->givePermissionTo('forms.res-044.endorse');

        $res044 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-044', groupId: $group->id);

        // Attempting RES-044 Dean approval transition is explicitly blocked
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RES-044 Dean approval transition is blocked');
        (new TransitionOfficialForm)->handle($dean, $res044, 'approve', 'approved');
    }

    public function test_res_046_requires_active_technical_editor_actor_assignment(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
        $editor->givePermissionTo('forms.res-046.certify');
        $group = $this->createGroup();
        $group->leader->givePermissionTo('forms.res-046.certify');

        $res046 = (new CreateOfficialFormInstance)->handle($group->leader, 'RES-046', $group->id);
        (new AssignOfficialFormActor)->handle($group->creator, $res046, $editor->id, 'technical_editor');

        $certified = (new TransitionOfficialForm)->handle($editor, $res046, 'certify', 'completed');
        $this->assertSame('completed', $certified->status);
    }

    public function test_completing_res_040_notifies_facilitator_and_research_instructor_that_res_041_is_required(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $instructor = User::factory()->create(['user_type' => 'faculty']);
        $instructor->givePermissionTo(
            'forms.res-040.receive',
            'forms.res-041.fill',
            'forms.res-041.endorse',
        );
        $group = $this->createGroup(adviser: $adviser);
        $facilitator = $group->researchClass->facilitator;

        (new AssignResearchClassFormActor)->handle(
            $facilitator,
            $group->researchClass,
            $instructor,
            'research_instructor',
        );

        $res040 = OfficialFormInstance::query()->create([
            'official_form_definition_id' => OfficialFormDefinition::query()->where('code', 'RES-040')->sole()->id,
            'research_class_group_id' => $group->id,
            'context_key' => 'general',
            'initiated_by' => $adviser->id,
            'status' => 'endorsed',
        ]);

        $this->assertSame(0, $facilitator->notifications()->count());
        $this->assertSame(0, $instructor->notifications()->count());

        $completed = (new TransitionOfficialForm)->handle(
            $instructor,
            $res040,
            'receive',
            'approved',
        );

        $this->assertSame('approved', $completed->status);

        foreach ([$facilitator, $instructor] as $recipient) {
            $notification = $recipient->notifications()->sole();

            $this->assertSame('official-form.next-required', $notification->data['event_key']);
            $this->assertStringContainsString('RES-041', $notification->data['title']);
            $this->assertStringContainsString($group->name, $notification->data['title']);
            $this->assertSame('official-forms.workspace.index', $notification->data['route_name']);
        }

        (new NotifyNextRequiredOfficialForms)->handle($instructor, $completed);

        $this->assertSame(1, $facilitator->notifications()->count());
        $this->assertSame(1, $instructor->notifications()->count());
    }

    public function test_official_form_payload_validator_rejects_client_injected_protected_keys(): void
    {
        $validator = new OfficialFormPayloadValidator;

        foreach (['signer_id', 'approver_id', 'roster', 'validation_average', 'mean_score', 'target_status'] as $key) {
            try {
                $validator->validate('RES-029', [$key => 'malicious_injection']);
                $this->fail("Expected key [{$key}] to be rejected as a protected system key.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('cannot specify system-managed key', $e->getMessage());
            }
        }
    }

    public function test_unverified_institutional_transitions_fail_closed(): void
    {
        $authorization = new OfficialFormAuthorization;

        foreach ([
            'RES-033' => ['approve'],
            'RES-042' => ['approve'],
            'RES-044' => ['approve'],
        ] as $code => $actions) {
            $instance = new OfficialFormInstance;
            $instance->setRelation('definition', OfficialFormDefinition::query()->where('code', $code)->sole());

            foreach ($actions as $action) {
                $this->assertNull($authorization->transitionFor($instance, $action), "{$code}.{$action} must fail closed.");
            }
        }
    }
}

<?php

namespace Tests\Feature\OfficialForms;

use App\Enums\ConsultationMode;
use App\Models\ConsultationRecord;
use App\Models\ConsultationRequest;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\OfficialForms\Actions\ApproveOfficialForm;
use App\Modules\OfficialForms\Actions\AssignOfficialFormActor;
use App\Modules\OfficialForms\Actions\CertifyOfficialForm;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
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
        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $leaderUser = $leader ?? User::factory()->create(['user_type' => 'student']);

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

    public function test_ownership_scope_validation_enforces_group_or_class(): void
    {
        $faculty = User::factory()->create(['user_type' => 'faculty']);

        $action = new CreateOfficialFormInstance;

        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $faculty,
            formCode: 'RES-041'
        );
    }

    public function test_single_per_group_cardinality_prevents_duplicate_instance(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup(leader: $student);
        $student->update(['research_class_group_id' => $group->id]);

        $action = new CreateOfficialFormInstance;
        $action->handle($student, 'RES-026', $group->id);

        $this->expectException(InvalidArgumentException::class);
        $action->handle($student, 'RES-026', $group->id);
    }

    public function test_per_actor_cardinality_allows_different_panelists_for_same_context_and_blocks_duplicates(): void
    {
        $panelist1 = User::factory()->create(['user_type' => 'faculty']);
        $panelist2 = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup();

        $action = new CreateOfficialFormInstance;
        $instance1 = $action->handle(
            initiator: $panelist1,
            formCode: 'RES-036',
            groupId: $group->id,
            contextKey: 'proposal_defense',
            actorUserId: $panelist1->id
        );
        $this->assertInstanceOf(OfficialFormInstance::class, $instance1);

        $instance2 = $action->handle(
            initiator: $panelist2,
            formCode: 'RES-036',
            groupId: $group->id,
            contextKey: 'proposal_defense',
            actorUserId: $panelist2->id
        );
        $this->assertInstanceOf(OfficialFormInstance::class, $instance2);

        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $panelist1,
            formCode: 'RES-036',
            groupId: $group->id,
            contextKey: 'proposal_defense',
            actorUserId: $panelist1->id
        );
    }

    public function test_version_payload_remains_immutable_after_approval_or_certification(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($adviser, 'RES-033', $group->id, contextKey: 'proposal_defense', payload: ['original_field' => 'untouched_data']);

        $initialPayload = $instance->currentVersion->payload;

        $approveAction = new ApproveOfficialForm;
        $approvedInstance = $approveAction->handle($adviser, $instance, ['remarks' => 'Approved']);

        $this->assertSame('approved', $approvedInstance->status);
        $this->assertSame($initialPayload, $approvedInstance->currentVersion->payload);
    }

    public function test_assigning_actor_validates_faculty_user_type_and_allowed_form_actor_types(): void
    {
        $faculty = User::factory()->create(['user_type' => 'faculty']);
        $student = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($faculty, 'RES-045', $group->id);

        $assignAction = new AssignOfficialFormActor;

        // Non-faculty assignment throws exception
        try {
            $assignAction->handle($faculty, $instance, $student->id, 'language_editor');
            $this->fail('Expected InvalidArgumentException for student user type');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('faculty user', $e->getMessage());
        }

        // Invalid actor type for RES-045 throws exception
        try {
            $assignAction->handle($faculty, $instance, $faculty->id, 'panelist');
            $this->fail('Expected InvalidArgumentException for invalid actor type');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not valid for form RES-045', $e->getMessage());
        }

        $assignment = $assignAction->handle($faculty, $instance, $faculty->id, 'language_editor');
        $this->assertSame('language_editor', $assignment->actor_type);
    }

    public function test_workflow_transition_protection_blocks_invalid_status_changes(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $editor->givePermissionTo('forms.res-045.certify');
        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($editor, 'RES-045', $group->id);

        $certifyAction = new CertifyOfficialForm;
        $completedInstance = $certifyAction->handle($editor, $instance);

        $this->assertSame('completed', $completedInstance->status);

        $approveAction = new ApproveOfficialForm;
        $this->expectException(InvalidArgumentException::class);
        $approveAction->handle($editor, $completedInstance);
    }

    public function test_source_linkage_validation_prevents_cross_group_idor(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $group1 = $this->createGroup(leader: $student);
        $group2 = $this->createGroup();

        $consultationReq = ConsultationRequest::query()->forceCreate([
            'research_class_group_id' => $group2->id,
            'requested_by' => $student->id,
            'assigned_adviser_id' => $group2->created_by,
            'request_token' => (string) Str::uuid(),
            'preferred_at' => now()->addDays(1),
            'consultation_mode' => ConsultationMode::InPerson->value,
            'agenda' => 'Request agenda',
            'status' => 'approved',
        ]);

        $consultationRecord = ConsultationRecord::query()->forceCreate([
            'consultation_request_id' => $consultationReq->id,
            'research_class_group_id' => $group2->id,
            'conducted_by' => $group2->created_by,
            'consulted_at' => now(),
            'consultation_mode' => ConsultationMode::InPerson->value,
            'agenda' => 'Cross-group test',
            'discussion' => 'Test discussion',
            'recommendations' => 'Test recommendations',
        ]);

        $action = new CreateOfficialFormInstance;
        $this->expectException(InvalidArgumentException::class);
        $action->handle(
            initiator: $student,
            formCode: 'RES-031',
            groupId: $group1->id,
            sourceType: ConsultationRecord::class,
            sourceId: $consultationRecord->id
        );
    }
}

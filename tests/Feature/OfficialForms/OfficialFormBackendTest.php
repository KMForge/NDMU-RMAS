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

        $action = new CreateOfficialFormInstance;
        $action->handle($student, 'RES-026', $group->id);

        $this->expectException(InvalidArgumentException::class);
        $action->handle($student, 'RES-026', $group->id);
    }

    public function test_submitting_new_version_increments_version_number_and_maintains_single_current_version(): void
    {
        $student = User::factory()->create(['user_type' => 'student']);
        $group = $this->createGroup(leader: $student);

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($student, 'RES-026', $group->id, payload: ['title' => 'Initial']);

        $submitAction = new SubmitOfficialFormVersion;
        $version2 = $submitAction->handle(
            actor: $student,
            instance: $instance,
            payload: ['title' => 'Updated Version 2'],
            nextStatus: 'submitted'
        );

        $instance->refresh();
        $this->assertSame(2, $version2->version_number);
        $this->assertTrue($version2->is_current);
        $this->assertSame($version2->id, $instance->current_version_id);
        $this->assertSame('submitted', $instance->status);

        $this->assertSame(1, $instance->versions()->where('is_current', true)->count());
    }

    public function test_assigning_official_form_actor(): void
    {
        $faculty = User::factory()->create(['user_type' => 'faculty']);
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($faculty, 'RES-045', $group->id);

        $assignAction = new AssignOfficialFormActor;
        $assignment = $assignAction->handle(
            assigner: $faculty,
            instance: $instance,
            userId: $editor->id,
            actorType: 'language_editor'
        );

        $this->assertSame('language_editor', $assignment->actor_type);
        $this->assertSame($editor->id, $assignment->user_id);
    }

    public function test_approving_official_form_updates_status_and_logs_audit(): void
    {
        $adviser = User::factory()->create(['user_type' => 'faculty']);
        $group = $this->createGroup(adviser: $adviser);

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($adviser, 'RES-033', $group->id, contextKey: 'proposal_defense');

        $approveAction = new ApproveOfficialForm;
        $approvedInstance = $approveAction->handle($adviser, $instance, ['remarks' => 'Endorsed for defense']);

        $this->assertSame('approved', $approvedInstance->status);
        $this->assertNotNull($approvedInstance->currentVersion->payload['approved_at']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'official_form.approved',
            'auditable_id' => $instance->id,
        ]);
    }

    public function test_certifying_editing_form_completes_instance(): void
    {
        $editor = User::factory()->create(['user_type' => 'faculty']);
        $editor->assignRole('language-editor');
        $group = $this->createGroup();

        $createAction = new CreateOfficialFormInstance;
        $instance = $createAction->handle($editor, 'RES-045', $group->id);

        $certifyAction = new CertifyOfficialForm;
        $completedInstance = $certifyAction->handle($editor, $instance, ['comments' => 'Grammar verified']);

        $this->assertSame('completed', $completedInstance->status);
        $this->assertNotNull($completedInstance->currentVersion->payload['certified_at']);
    }
}

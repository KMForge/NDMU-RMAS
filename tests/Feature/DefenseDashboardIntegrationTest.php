<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DefenseDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $student;

    private User $adviser;

    private User $panelist;

    private ResearchClassGroup $group;

    private DefenseRoom $room;

    private DefenseSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.facilitator.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.student.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.adviser.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.panelist.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'forms.res-036.evaluate', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'documents.download', 'guard_name' => 'web']);

        $this->facilitator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->facilitator->givePermissionTo(['defenses.manage', 'dashboards.facilitator.view']);

        $this->student = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->student->givePermissionTo('dashboards.student.view');

        $this->adviser = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->adviser->givePermissionTo('dashboards.adviser.view');

        $this->panelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->panelist->givePermissionTo(['dashboards.panelist.view', 'forms.res-036.evaluate', 'evaluations.create', 'documents.download']);

        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => Crypt::encryptString('CAP-123456'),
            'is_active' => true,
        ]);

        $this->group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group Beta',
            'leader_student_id' => $this->student->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $this->room = DefenseRoom::create([
            'code' => 'RM-303',
            'name' => 'Innovation Lab',
            'is_active' => true,
        ]);

        $startsAt = Carbon::now()->addDays(2)->setHour(14)->setMinute(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $scheduleAction = app(ScheduleDefense::class);
        $defense = $scheduleAction->handle(
            $this->facilitator,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            [$this->panelist->id]
        );

        $this->schedule = DefenseSchedule::findOrFail($defense->current_schedule_id);
    }

    public function test_panelist_dashboard_renders_assigned_defense_and_res036_gateway(): void
    {
        $response = $this->actingAs($this->panelist)->get('/panelist/dashboard?tab=schedule');

        $response->assertStatus(200);
        $response->assertSee('RM-303');
        $response->assertSee('Group Beta');
        $response->assertSee('Proposal Defense');
        $response->assertSee('RES-036');
    }

    public function test_unassigned_panelist_does_not_see_other_defense(): void
    {
        $unassignedPanelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $unassignedPanelist->givePermissionTo(['dashboards.panelist.view', 'forms.res-036.evaluate', 'evaluations.create']);

        $response = $this->actingAs($unassignedPanelist)->get('/panelist/dashboard?tab=schedule');

        $response->assertStatus(200);
        $response->assertDontSee('Group Beta');
    }

    public function test_proposal_evaluation_lists_the_current_paper_for_the_assigned_panelist(): void
    {
        $document = Document::query()->create([
            'user_id' => $this->student->id,
            'research_class_group_id' => $this->group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'Group Beta Proposal.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => DocumentStage::ProposalDefense,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', 'Group Beta Proposal.pdf'),
            'submitted_at' => now(),
            'status' => DocumentStatus::Accepted,
        ]);

        $response = $this->actingAs($this->panelist)
            ->get(route('panelist.dashboard', ['tab' => 'proposal-eval']));

        $response->assertOk()
            ->assertSee('Group Beta Proposal.pdf')
            ->assertSee(':href="p.viewUrl"', false)
            ->assertSee(':href="p.downloadUrl"', false);

        $proposalPaper = collect($response->viewData('proposalPapers'))->firstWhere('id', $document->id);

        $this->assertNotNull($proposalPaper);
        $this->assertSame(route('documents.view', $document), $proposalPaper['viewUrl']);
        $this->assertSame(route('documents.download', $document), $proposalPaper['downloadUrl']);
    }

    public function test_assigned_panelist_can_open_the_large_preview_and_post_a_comment_before_scoring_opens(): void
    {
        $enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $this->group->research_class_id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'requested_at' => now(),
            'joined_at' => now(),
            'reviewed_by' => $this->facilitator->id,
            'reviewed_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $this->group->id,
            'research_class_id' => $this->group->research_class_id,
            'research_class_enrollment_id' => $enrollment->id,
            'student_id' => $this->student->id,
            'assigned_by' => $this->facilitator->id,
        ]);

        $document = Document::query()->create([
            'user_id' => $this->student->id,
            'research_class_group_id' => $this->group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'Proposal For Panel Review.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => DocumentStage::ProposalDefense,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 2048,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', 'Proposal For Panel Review.pdf'),
            'submitted_at' => now(),
            'status' => DocumentStatus::Accepted,
        ]);

        $this->actingAs($this->panelist)
            ->get(route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->id,
            ]))
            ->assertOk()
            ->assertSee('Document Preview')
            ->assertSeeText('Comments & Feedback')
            ->assertSee('Proposal For Panel Review.pdf');

        $this->actingAs($this->panelist)
            ->post(route('panelist.documents.comments.store', $document), [
                'comment' => 'Clarify the sampling method before the defense.',
                'severity' => 'revision',
                'page_number' => 8,
            ])
            ->assertRedirect(route('panelist.dashboard', [
                'tab' => 'recommendations',
                'document_id' => $document->id,
            ]));

        $this->assertDatabaseHas('document_review_comments', [
            'document_id' => $document->id,
            'author_id' => $this->panelist->id,
            'comment' => 'Clarify the sampling method before the defense.',
            'severity' => 'revision',
            'page_number' => 8,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->student->id,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.dashboard', ['tab' => 'revisions']))
            ->assertOk()
            ->assertSeeText('Clarify the sampling method before the defense.')
            ->assertSeeText('Proposal For Panel Review.pdf')
            ->assertSeeText($this->panelist->name);
    }

    public function test_unassigned_panelist_cannot_comment_on_another_groups_paper(): void
    {
        $document = Document::query()->create([
            'user_id' => $this->student->id,
            'research_class_group_id' => $this->group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'Protected Proposal.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => DocumentStage::ProposalDefense,
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 2048,
            'storage_disk' => 'local',
            'storage_path' => 'documents/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', 'Protected Proposal.pdf'),
            'submitted_at' => now(),
            'status' => DocumentStatus::Accepted,
        ]);
        $unassignedPanelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $unassignedPanelist->givePermissionTo(['dashboards.panelist.view', 'evaluations.create']);

        $this->actingAs($unassignedPanelist)
            ->postJson(route('panelist.documents.comments.store', $document), [
                'comment' => 'This must not be stored.',
                'severity' => 'comment',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('document_review_comments', [
            'document_id' => $document->id,
            'author_id' => $unassignedPanelist->id,
        ]);
    }

    public function test_facilitator_dashboard_safely_serializes_populated_defense_data(): void
    {
        $response = $this->actingAs($this->facilitator)->get('/facilitator/dashboard?tab=defenses');

        $response->assertOk();
        $response->assertSee('defenseList: JSON.parse(', false);
        $response->assertSee('Group Beta');
        $response->assertSee('Innovation Lab');
    }
}

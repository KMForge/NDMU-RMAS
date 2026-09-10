<?php

namespace Tests\Feature\TitlePresentations;

use App\Enums\AccountStatus;
use App\Enums\DocumentStatus;
use App\Enums\ResearchMilestoneStatus;
use App\Enums\UserType;
use App\Http\Controllers\OfficialFormWorkspaceController;
use App\Models\DefenseRoom;
use App\Models\Document;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupPanelCommittee;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\Documents\Actions\ScreenTitleProposalDocument;
use App\Modules\Documents\Actions\SubmitTitleProposalForScreening;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SubmitOfficialFormVersion;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\ResearchProgress\Actions\SynchronizeWorkflowMilestone;
use App\Modules\ResearchProgress\Services\ResearchJourneyService;
use App\Modules\TitlePresentations\Actions\AssignTitlePresentationPanel;
use App\Modules\TitlePresentations\Actions\CompleteTitlePresentation;
use App\Modules\TitlePresentations\Actions\RecordApprovedTitle;
use App\Modules\TitlePresentations\Actions\ScheduleTitlePresentation;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class LockedTitlePresentationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $facilitator;

    private ResearchClassGroup $group;

    private DefenseRoom $room;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Config::set('signatures.verification_key', 'test_secret_verification_key_32_bytes_long!!');
        Config::set('signatures.verification_key_version', 'v1');
        $this->seed(RolePermissionSeeder::class);
        (new SyncOfficialFormCatalog)->handle();

        $this->student = $this->eligibleUser(UserType::Student, ['forms.res-026.fill', 'forms.res-026.submit', 'documents.upload']);
        $this->facilitator = $this->eligibleUser(UserType::Faculty, ['documents.review', 'defenses.manage', 'dashboards.facilitator.view']);
        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone II',
            'join_code_hash' => hash('sha256', Str::random()),
            'join_code_encrypted' => Crypt::encryptString('TESTCODE'),
            'is_active' => true,
        ]);
        $this->group = ResearchClassGroup::query()->create([
            'research_class_id' => $class->id,
            'leader_student_id' => $this->student->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group Alpha',
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);
        $this->room = DefenseRoom::query()->create(['code' => 'TP-301', 'name' => 'Room 301', 'is_active' => true]);
    }

    public function test_res026_is_locked_until_current_title_proposal_is_approved_for_presentation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must first be approved');
        app(CreateOfficialFormInstance::class)->handle($this->student, 'RES-026', $this->group->id);
    }

    public function test_journey_starts_at_title_proposal_document_then_recommends_res026_after_approval(): void
    {
        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group, $this->student);

        $this->assertSame(1, $journey['current_stage']);
        $this->assertSame('Research Title Presentation', $journey['current_stage_name']);
        $this->assertSame('Upload Title Proposal Document', $journey['next_action']['label']);
        $this->assertSame('document', $journey['next_action']['action_type']);
        $this->assertNull($journey['next_action']['form_code']);
        $this->assertSame(route('student.dashboard', ['tab' => 'proposal']), $journey['next_action']['route']);

        $this->titleDocument(DocumentStatus::ApprovedForPresentation);
        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);

        $this->assertSame(1, $journey['current_stage']);
        $this->assertSame('Create RES-026', $journey['next_action']['label']);
        $this->assertSame('form', $journey['next_action']['action_type']);
        $this->assertSame('res-026', $journey['next_action']['form_code']);
        $this->assertSame(route('official-forms.workspace.index', [
            'form' => 'RES-026',
            'group_id' => $this->group->id,
        ]), $journey['next_action']['route']);
    }

    public function test_journey_does_not_count_a_later_completed_stage_before_earlier_stages(): void
    {
        app(SynchronizeWorkflowMilestone::class)->complete(
            $this->group,
            'revision-research-proposal',
            $this->facilitator,
            'official_form',
            999,
            'Later-stage artifact imported before prerequisites.',
        );

        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);

        $this->assertSame(1, $journey['current_stage']);
        $this->assertSame(0, $journey['percentage']);
        $this->assertFalse($journey['stages'][4]['is_completed']);
    }

    public function test_approved_document_unlocks_res026_and_submission_requires_exactly_three_titles(): void
    {
        $this->titleDocument(DocumentStatus::ApprovedForPresentation);
        $instance = app(CreateOfficialFormInstance::class)->handle($this->student, 'RES-026', $this->group->id);

        try {
            app(SubmitOfficialFormVersion::class)->handle($this->student, $instance, ['topics' => ['One', 'Two']]);
            $this->fail('Two titles must not be accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('exactly three', $exception->getMessage());
        }

        $version = app(SubmitOfficialFormVersion::class)->handle($this->student, $instance, ['topics' => ['Title A', 'Title B', 'Title C']]);
        $this->assertSame(['Title A', 'Title B', 'Title C'], $version->payload['topics']);
        $this->assertSame('submitted', $instance->fresh()->status);
    }

    public function test_title_proposal_submission_automatically_starts_the_first_milestone_and_links_evidence(): void
    {
        $document = $this->titleDocument(DocumentStatus::Draft);

        app(SubmitTitleProposalForScreening::class)->handle($this->student, $document);

        $milestone = ResearchGroupMilestone::query()
            ->where('research_class_group_id', $this->group->id)
            ->whereHas('definition', fn ($query) => $query->where('code', 'research-title-presentation'))
            ->with('evidences')
            ->firstOrFail();

        $this->assertSame(ResearchMilestoneStatus::InProgress, $milestone->status);
        $this->assertSame($this->student->id, $milestone->started_by);
        $this->assertTrue($milestone->evidences->contains(
            fn ($evidence): bool => $evidence->evidence_type === 'document'
                && $evidence->evidence_id === $document->id,
        ));
    }

    public function test_only_owning_facilitator_can_screen_and_revision_requires_remarks(): void
    {
        $document = $this->titleDocument(DocumentStatus::Submitted);
        $other = $this->eligibleUser(UserType::Faculty, ['documents.review']);

        $this->expectException(AuthorizationException::class);
        app(ScreenTitleProposalDocument::class)->handle($other, $document, 'approved_for_presentation', null);
    }

    public function test_facilitator_sidebar_badge_counts_title_proposals_awaiting_screening(): void
    {
        $document = $this->titleDocument(DocumentStatus::Submitted);

        $this->actingAs($this->facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'screening']))
            ->assertOk()
            ->assertSee("activeTab: 'screening'", false)
            ->assertSee('queuePersistTab(tab)', false)
            ->assertSee('aria-label="1 title proposal awaiting screening"', false);

        app(ScreenTitleProposalDocument::class)->handle(
            $this->facilitator,
            $document,
            'approved_for_presentation',
            null,
        );

        $this->get(route('facilitator.dashboard', ['tab' => 'screening']))
            ->assertOk()
            ->assertDontSee('aria-label="1 title proposal awaiting screening"', false)
            ->assertSee('Screening &amp; Review History', false)
            ->assertSee($document->original_filename)
            ->assertSee('Approved For Presentation');
    }

    public function test_schedule_then_exact_panel_with_adviser_chair_conflict_and_server_derived_result(): void
    {
        $this->titleDocument(DocumentStatus::ApprovedForPresentation);
        $instance = app(CreateOfficialFormInstance::class)->handle($this->student, 'RES-026', $this->group->id);
        app(SubmitOfficialFormVersion::class)->handle($this->student, $instance, ['topics' => ['Title A', 'Title B', 'Title C']]);

        $starts = Carbon::now()->addWeek()->setHour(10)->setMinute(0)->setSecond(0);
        $presentation = app(ScheduleTitlePresentation::class)->handle($this->facilitator, $instance->fresh(), $this->room->id, $starts, $starts->copy()->addHour());

        $adviser = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $memberOne = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $memberTwo = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $this->group->update(['adviser_id' => $adviser->id]);

        try {
            app(AssignTitlePresentationPanel::class)->handle($this->facilitator, $presentation, [
                'chairperson' => $adviser->id, 'member_1' => $memberOne->id, 'member_2' => $memberTwo->id,
            ]);
            $this->fail('The same-group adviser must not chair.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('cannot serve as the Chairperson', $exception->getMessage());
        }

        $chair = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $assigned = app(AssignTitlePresentationPanel::class)->handle($this->facilitator, $presentation, [
            'chairperson' => $chair->id, 'member_1' => $adviser->id, 'member_2' => $memberTwo->id,
        ]);
        $this->assertSame(3, $assigned->defense->activePanelAssignments->count());
        $this->assertSame($adviser->id, $assigned->defense->activePanelAssignments->firstWhere('panel_position', 'member_1')->user_id);

        app(CompleteTitlePresentation::class)->handle($this->facilitator, $assigned);
        $result = app(RecordApprovedTitle::class)->handle($this->facilitator, $assigned->fresh(), 2, 'Approved during presentation.');
        $this->assertSame(2, $result->approved_title_number);
        $this->assertSame('Title B', $result->formVersion->payload['topics'][$result->approved_title_number - 1]);
        $this->assertSame('awaiting_panel_signatures', $result->status);
    }

    public function test_saved_title_presentation_committee_can_be_reused_without_a_historical_change_reason(): void
    {
        $this->titleDocument(DocumentStatus::ApprovedForPresentation);
        $instance = app(CreateOfficialFormInstance::class)->handle($this->student, 'RES-026', $this->group->id);
        app(SubmitOfficialFormVersion::class)->handle($this->student, $instance, ['topics' => ['Title A', 'Title B', 'Title C']]);

        $chair = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $memberOne = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $memberTwo = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $committee = ResearchGroupPanelCommittee::query()->create([
            'research_class_group_id' => $this->group->id,
            'defense_type' => 'title_presentation',
            'chairperson_id' => $chair->id,
            'is_custom' => false,
            'created_by' => $this->facilitator->id,
            'updated_by' => $this->facilitator->id,
        ]);
        $committee->members()->createMany([
            ['user_id' => $memberOne->id, 'panel_position' => 'member_1'],
            ['user_id' => $memberTwo->id, 'panel_position' => 'member_2'],
        ]);

        $starts = Carbon::now()->addWeek()->setHour(10)->setMinute(0)->setSecond(0);
        $presentation = app(ScheduleTitlePresentation::class)->handle(
            $this->facilitator,
            $instance->fresh(),
            $this->room->id,
            $starts,
            $starts->copy()->addHour(),
        );

        $assigned = app(AssignTitlePresentationPanel::class)->handle($this->facilitator, $presentation, [
            'chairperson' => $chair->id,
            'member_1' => $memberOne->id,
            'member_2' => $memberTwo->id,
        ]);

        $this->assertSame('panel_assigned', $assigned->status);
        $this->assertCount(3, $assigned->defense->activePanelAssignments);
        $this->assertDatabaseCount('defense_panel_assignments', 3);

        $replacement = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A reason is required when changing a historical Title Presentation panel assignment.');
        app(AssignTitlePresentationPanel::class)->handle($this->facilitator, $assigned, [
            'chairperson' => $chair->id,
            'member_1' => $memberOne->id,
            'member_2' => $replacement->id,
        ]);
    }

    public function test_exact_panel_coordinator_and_dean_signatures_finalize_the_canonical_title(): void
    {
        $this->seedCanonicalResearchContext();
        $this->titleDocument(DocumentStatus::ApprovedForPresentation);
        $instance = app(CreateOfficialFormInstance::class)->handle($this->student, 'RES-026', $this->group->id);
        app(SubmitOfficialFormVersion::class)->handle($this->student, $instance, ['topics' => ['Title A', 'Canonical Title B', 'Title C']]);

        $starts = Carbon::now()->addWeeks(2)->setHour(10)->setMinute(0)->setSecond(0);
        $presentation = app(ScheduleTitlePresentation::class)->handle($this->facilitator, $instance->fresh(), $this->room->id, $starts, $starts->copy()->addHour());
        $chair = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $memberOne = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $memberTwo = $this->eligibleUser(UserType::Faculty, ['evaluations.create']);
        $presentation = app(AssignTitlePresentationPanel::class)->handle($this->facilitator, $presentation, [
            'chairperson' => $chair->id,
            'member_1' => $memberOne->id,
            'member_2' => $memberTwo->id,
        ]);
        app(CompleteTitlePresentation::class)->handle($this->facilitator, $presentation);
        $presentation = app(RecordApprovedTitle::class)->handle($this->facilitator, $presentation->fresh(), 2);

        // Keep the coordinator separate here so this test isolates the complete
        // panel-to-coordinator-to-dean finalization path.
        $coordinator = $this->eligibleUser(UserType::Faculty, ['forms.res-026.approve']);
        $coordinator->givePermissionTo('forms.res-026.approve');
        $dean = $this->eligibleUser(UserType::Faculty, ['dashboards.dean.view']);
        $dean->assignRole('dean');
        foreach ([
            [$coordinator, 'program_coordinator'],
        ] as [$user, $actorType]) {
            ResearchClassActorAssignment::query()->create([
                'research_class_id' => $this->group->research_class_id,
                'user_id' => $user->id,
                'actor_type' => $actorType,
                'assigned_by' => $this->facilitator->id,
                'assigned_at' => now(),
                'status' => 'active',
            ]);
        }

        foreach ([$chair, $memberOne, $memberTwo, $coordinator, $dean] as $signer) {
            $this->enrollSignature($signer);
        }

        $sign = app(ApplyOfficialFormSignature::class);
        $instance->refresh();
        $versionId = (int) $presentation->official_form_version_id;

        $chairPendingActions = app(GetPendingAcademicActionsForUser::class)->execute($chair);
        $this->assertTrue(
            $chairPendingActions->contains(
                fn (array $action): bool => $action['instance_id'] === $instance->id
                    && $action['action'] === 'sign_chairperson',
            ),
            'An exact RES-026 Chairperson assignment must expose the form even without a general forms.res-026 permission.',
        );

        $memberTwoRequest = Request::create('/');
        $memberTwoRequest->setUserResolver(static fn (): User => $memberTwo);
        $this->assertTrue(
            app(OfficialFormWorkspaceController::class)
                ->pendingInstances($memberTwoRequest)
                ->contains('id', $instance->id),
            'The assigned panel member must receive an account-scoped pending form badge.',
        );

        try {
            $sign->handle($memberOne, $instance->id, $versionId, 'sign_chairperson');
            $this->fail('A Panel Member must not sign the Chairperson field.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not authorized', $exception->getMessage());
        }

        $sign->handle($chair, $instance->id, $versionId, 'sign_chairperson');
        $sign->handle($memberOne, $instance->id, $versionId, 'sign_member_1');
        $this->actingAs($memberTwo)
            ->post(route('official-forms.workspace.sign-action', [$instance, 'sign_member_2']), [
                'expected_version_id' => $versionId,
            ])
            ->assertRedirect();
        $this->assertSame('awaiting_program_coordinator', $presentation->fresh()->status);
        $this->actingAs($coordinator)
            ->get(route('official-forms.workspace.show', $instance))
            ->assertOk()
            ->assertDontSee('Sign as Panel Member 2')
            ->assertSee('Sign &amp; Endorse', false);

        $sign->handle($coordinator, $instance->id, $versionId, 'endorse');
        $this->assertSame('awaiting_dean', $presentation->fresh()->status);

        $deanPendingActions = app(GetPendingAcademicActionsForUser::class)->execute($dean);
        $this->assertTrue($deanPendingActions->contains(
            fn (array $action): bool => $action['instance_id'] === $instance->id
                && $action['action'] === 'approve',
        ));
        $this->actingAs($dean)
            ->get(route('official-forms.workspace.show', $instance))
            ->assertOk()
            ->assertSee($dean->name)
            ->assertSee('Sign &amp; Approve', false);

        $sign->handle($dean, $instance->id, $versionId, 'approve');
        $this->assertSame('finalized', $presentation->fresh()->status);
        $this->assertDatabaseHas('research_projects', [
            'research_group_id' => $this->group->fresh()->research_group_id,
            'title' => 'Canonical Title B',
            'status' => 'approved',
        ]);
        $this->assertSame(
            'Canonical Title B',
            $this->group->fresh()->researchGroup?->currentProject?->title,
        );

        $milestone = ResearchGroupMilestone::query()
            ->where('research_class_group_id', $this->group->id)
            ->whereHas('definition', fn ($query) => $query->where('code', 'research-title-presentation'))
            ->with('evidences')
            ->firstOrFail();
        $this->assertSame(ResearchMilestoneStatus::Completed, $milestone->status);
        $this->assertSame($dean->id, $milestone->completed_by);
        $this->assertTrue($milestone->evidences->contains(
            fn ($evidence): bool => $evidence->evidence_type === 'official_form'
                && $evidence->evidence_id === $instance->id,
        ));

        $eventCount = $milestone->events()->count();
        app(SynchronizeWorkflowMilestone::class)->complete(
            $this->group,
            'research-title-presentation',
            $dean,
            'official_form',
            $instance->id,
            'Finalized RES-026 Research Title Approval with all required digital signatures.',
        );
        $this->assertSame($eventCount, $milestone->events()->count(), 'Workflow reconciliation must be idempotent.');

        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);
        $this->assertSame(2, $journey['current_stage']);
        $this->assertSame(8, $journey['percentage']);
    }

    private function eligibleUser(UserType $type, array $permissions): User
    {
        $user = User::factory()->create([
            'user_type' => $type,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function titleDocument(DocumentStatus $status): Document
    {
        return Document::query()->forceCreate([
            'user_id' => $this->student->id,
            'research_class_group_id' => $this->group->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'title-proposal.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'document_stage' => 'title_proposal',
            'version_number' => 1,
            'is_current' => true,
            'file_size' => 1024,
            'storage_disk' => 'local',
            'storage_path' => 'documents/title-proposal.pdf',
            'content_sha256' => hash('sha256', Str::uuid()->toString()),
            'submitted_at' => now(),
            'status' => $status,
        ]);
    }

    private function enrollSignature(User $user): void
    {
        $bytes = 'verified-signature-'.$user->id;
        $path = "signatures/{$user->id}.png";
        Storage::disk('local')->put($path, $bytes);
        UserSignature::query()->create([
            'user_id' => $user->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'original_filename' => 'signature.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($bytes),
            'content_sha256' => hash('sha256', $bytes),
            'registered_at' => now(),
        ]);
    }

    private function seedCanonicalResearchContext(): void
    {
        $now = now();
        $collegeId = DB::table('colleges')->insertGetId(['code' => 'CEAC-TEST', 'name' => 'CEAC Test', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $departmentId = DB::table('departments')->insertGetId(['college_id' => $collegeId, 'code' => 'CS-TEST', 'name' => 'Computing Test', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('programs')->insertGetId(['department_id' => $departmentId, 'code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        $yearId = DB::table('academic_years')->insertGetId(['name' => '2098-2099', 'starts_at' => '2098-06-01', 'ends_at' => '2099-05-31', 'is_current' => false, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('academic_terms')->insertGetId(['academic_year_id' => $yearId, 'name' => 'Title Test Term', 'starts_at' => '2098-06-01', 'ends_at' => '2098-10-31', 'is_current' => true, 'created_at' => $now, 'updated_at' => $now]);
        $this->student->update([
            'student_id' => 'TITLE-TEST-001',
            'program' => config('academic.programs.3.label'),
            'year_level' => '4th',
        ]);
    }
}

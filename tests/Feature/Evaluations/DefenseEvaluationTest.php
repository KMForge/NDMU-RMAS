<?php

namespace Tests\Feature\Evaluations;

use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\Evaluations\Actions\OpenDefenseEvaluationRound;
use App\Modules\Evaluations\Actions\ReleaseDefenseEvaluationResults;
use App\Modules\Evaluations\Actions\SaveDefenseEvaluationDraft;
use App\Modules\Evaluations\Actions\SubmitDefenseEvaluation;
use App\Modules\Evaluations\Services\Res036Rubric;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SubmitOfficialFormVersion;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DefenseEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $adviser;

    private User $student1;

    private User $student2;

    private User $panelist1;

    private User $panelist2;

    private User $panelist3;

    private Defense $defense;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed();
        config(['signatures.verification_key' => 'test-secret-key-12345678901234567890123456789012']);
        (new SyncOfficialFormCatalog)->handle();

        // Create Users
        $this->facilitator = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->facilitator->givePermissionTo(['defenses.manage', 'evaluations.release', 'dashboards.facilitator.view']);

        $this->adviser = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->adviser->assignRole('thesis-adviser');
        $this->adviser->givePermissionTo(['evaluations.view-assigned']);

        $this->student1 = User::factory()->create([
            'user_type' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->student1->assignRole('student-researcher');
        $this->student1->givePermissionTo(['evaluations.view-own']);

        $this->student2 = User::factory()->create([
            'user_type' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->student2->assignRole('student-researcher');
        $this->student2->givePermissionTo(['evaluations.view-own']);

        $this->panelist1 = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->panelist1->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate', 'forms.res-037.sign', 'dashboards.panelist.view']);

        $this->panelist2 = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->panelist2->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate', 'forms.res-037.sign', 'dashboards.panelist.view']);

        $this->panelist3 = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->panelist3->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate', 'forms.res-037.sign', 'dashboards.panelist.view']);

        // Setup Class & Group
        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-123'),
            'join_code_encrypted' => 'CAP-123',
            'is_active' => true,
        ]);
        $group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group 1',
            'leader_student_id' => $this->student1->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
            'adviser_id' => $this->adviser->id,
        ]);
        $enrollment1 = ResearchClassEnrollment::query()->forceCreate([
            'research_class_id' => $class->id,
            'student_id' => $this->student1->id,
            'status' => 'active',
        ]);
        $enrollment2 = ResearchClassEnrollment::query()->forceCreate([
            'research_class_id' => $class->id,
            'student_id' => $this->student2->id,
            'status' => 'active',
        ]);

        ResearchClassGroupMember::query()->forceCreate([
            'research_class_id' => $class->id,
            'research_class_group_id' => $group->id,
            'research_class_enrollment_id' => $enrollment1->id,
            'student_id' => $this->student1->id,
            'assigned_by' => $this->facilitator->id,
        ]);
        ResearchClassGroupMember::query()->forceCreate([
            'research_class_id' => $class->id,
            'research_class_group_id' => $group->id,
            'research_class_enrollment_id' => $enrollment2->id,
            'student_id' => $this->student2->id,
            'assigned_by' => $this->facilitator->id,
        ]);

        // Room & Defense
        $room = DefenseRoom::create([
            'code' => 'RM-101',
            'name' => 'Conference Room A',
            'is_active' => true,
        ]);
        $this->defense = Defense::query()->forceCreate([
            'research_class_group_id' => $group->id,
            'defense_type' => 'proposal_defense',
            'status' => 'scheduled',
            'created_by' => $this->facilitator->id,
        ]);
        $schedule = DefenseSchedule::query()->forceCreate([
            'defense_id' => $this->defense->id,
            'room_id' => $room->id,
            'starts_at' => now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0),
            'ends_at' => now()->addDays(2)->setHour(11)->setMinute(0)->setSecond(0),
            'status' => 'current',
            'scheduled_by' => $this->facilitator->id,
        ]);
        $this->defense->update(['current_schedule_id' => $schedule->id]);

        // Assign 3 Panelists
        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $this->panelist1->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);
        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $this->panelist2->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);
        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $this->panelist3->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);
    }

    public function test_facilitator_can_open_defense_evaluation_round(): void
    {
        $action = new OpenDefenseEvaluationRound;
        $round = $action->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $this->assertInstanceOf(DefenseEvaluationRound::class, $round);
        $this->assertEquals('open', $round->status);
        $this->assertEquals($this->panelist1->id, $round->summary_signer_user_id);
        $this->assertCount(3, $round->roundPanelists);
        $this->assertCount(2, $round->roundStudents);
    }

    public function test_panelist_can_save_draft_and_submit_evaluation(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $draftAction = new SaveDefenseEvaluationDraft;
        $draft = $draftAction->handle($this->panelist1, $round, [
            'research_quality_score' => 85.5,
            'originality_score' => 90.0,
            'relevance_score' => 88.0,
            'general_comments' => 'Good progress.',
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 85, 'effectiveness_score' => 90],
            ],
        ]);

        $this->assertEquals('draft', $draft->status);

        $submitAction = new SubmitDefenseEvaluation;
        $evaluation = $submitAction->handle($this->panelist1, $round, [
            'research_quality_score' => 85.0,
            'originality_score' => 90.0,
            'relevance_score' => 88.0,
            'general_comments' => 'Good presentation.',
            'recommendations' => 'Revise Chapter 3.',
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 85, 'effectiveness_score' => 90],
                $this->student2->id => ['communication_score' => 90, 'organization_score' => 92, 'effectiveness_score' => 94],
            ],
        ]);

        $this->assertEquals('submitted', $evaluation->status);
        $this->assertNotNull($evaluation->submitted_at);
        $this->assertEquals(87.00, $evaluation->research_paper_total);

        // RES-036 form instance created with defense_evaluation_id
        $res036 = OfficialFormInstance::where('source_type', DefenseSchedule::class)
            ->where('source_id', $this->defense->current_schedule_id)
            ->where('defense_evaluation_id', $evaluation->id)
            ->first();

        $this->assertNotNull($res036);
        $this->assertEquals('submitted', $res036->status);
    }

    public function test_round_completes_and_generates_summary_when_all_panelists_submit(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;

        $payload = fn ($rq, $orig, $rel, $s1c, $s1o, $s1e, $s2c, $s2o, $s2e) => [
            'research_quality_score' => $rq,
            'originality_score' => $orig,
            'relevance_score' => $rel,
            'general_comments' => 'Solid work.',
            'student_scores' => [
                $this->student1->id => ['communication_score' => $s1c, 'organization_score' => $s1o, 'effectiveness_score' => $s1e],
                $this->student2->id => ['communication_score' => $s2c, 'organization_score' => $s2o, 'effectiveness_score' => $s2e],
            ],
        ];

        $submitAction->handle($this->panelist1, $round, $payload(90, 90, 90, 80, 80, 80, 90, 90, 90));
        $this->assertEquals('in_progress', $round->fresh()->status);

        $submitAction->handle($this->panelist2, $round, $payload(80, 80, 80, 85, 85, 85, 85, 85, 85));
        $this->assertEquals('in_progress', $round->fresh()->status);

        $submitAction->handle($this->panelist3, $round, $payload(85, 85, 85, 90, 90, 90, 95, 95, 95));

        $freshRound = $round->fresh(['summary.studentSummaries']);
        $this->assertEquals('complete', $freshRound->status);
        $this->assertNotNull($freshRound->all_submitted_at);
        $this->assertNotNull($freshRound->summary);

        // Research Paper Avg = (90 + 80 + 85) / 3 = 85.0
        $this->assertEquals(85.0, $freshRound->summary->research_paper_average);

        // Check RES-037 form instance created for round
        $res037 = OfficialFormInstance::where('source_type', DefenseEvaluationRound::class)
            ->where('source_id', $round->id)
            ->first();

        $this->assertNotNull($res037);
        $this->assertEquals('RES-037', $res037->definition->code);
    }

    public function test_panelist_submission_persists_the_detailed_res036_rubric(): void
    {
        $round = (new OpenDefenseEvaluationRound)->handle($this->facilitator, $this->defense, $this->panelist1->id);
        $paperScores = Res036Rubric::PAPER_MAXIMUMS;
        $presentationScores = Res036Rubric::PRESENTATION_MAXIMUMS;

        $evaluation = (new SubmitDefenseEvaluation)->handle($this->panelist1, $round, [
            'paper_scores' => $paperScores,
            'student_scores' => [
                $this->student1->id => ['presentation_scores' => $presentationScores],
                $this->student2->id => ['presentation_scores' => $presentationScores],
            ],
        ]);

        $this->assertSame(Res036Rubric::VERSION, $evaluation->rubric_version);
        $this->assertSame($paperScores, $evaluation->paper_criterion_scores);
        $this->assertEquals(100, $evaluation->research_paper_total);
        $this->assertSame($presentationScores, $evaluation->studentScores->first()->presentation_criterion_scores);
        $this->assertEquals(100, $evaluation->studentScores->first()->presentation_total);
    }

    public function test_panelist_can_sign_and_submit_the_visible_res036_rubric_without_legacy_totals(): void
    {
        (new OpenDefenseEvaluationRound)->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $instance = (new CreateOfficialFormInstance)->handle(
            $this->panelist1,
            'RES-036',
            $this->defense->research_class_group_id,
            sourceType: DefenseSchedule::class,
            sourceId: $this->defense->current_schedule_id,
        );

        UserSignature::create([
            'user_id' => $this->panelist1->id,
            'storage_disk' => 'local',
            'storage_path' => 'signatures/panelist-1.png',
            'original_filename' => 'panelist-1.png',
            'content_sha256' => hash('sha256', 'panelist-signature'),
            'file_size' => strlen('panelist-signature'),
            'mime_type' => 'image/png',
            'registered_at' => now(),
        ]);
        Storage::disk('local')->put('signatures/panelist-1.png', 'panelist-signature');

        $initialPayload = $instance->currentVersion->payload;
        $presenters = [];
        foreach (array_keys($initialPayload['res_036_presenters']) as $index) {
            $presenters[$index] = ['scores' => Res036Rubric::PRESENTATION_MAXIMUMS];
        }

        $submittedVersion = (new SubmitOfficialFormVersion)->handle($this->panelist1, $instance, [
            'res_036_defense_type' => $initialPayload['res_036_defense_type'],
            'res_036_date' => $initialPayload['res_036_date'],
            'res_036_time' => $initialPayload['res_036_time'],
            'res_036_venue' => $initialPayload['res_036_venue'],
            'res_036_research_title' => $initialPayload['res_036_research_title'],
            'res_036_paper_scores' => Res036Rubric::PAPER_MAXIMUMS,
            'res_036_presenters' => $presenters,
            'res_036_panelist_printed_name' => $initialPayload['res_036_panelist_printed_name'],
            'res_036_signed_at' => $initialPayload['res_036_signed_at'],
        ]);

        $this->assertSame('submitted', $instance->fresh()->status);
        $this->assertArrayNotHasKey('res_036_paper_total', $submittedVersion->payload);
        $this->assertSame($this->student1->name, $submittedVersion->payload['res_036_presenters'][1]['name']);
        $this->assertEquals(100, $instance->fresh()->defenseEvaluation->research_paper_total);
        $this->assertTrue($submittedVersion->signatures()
            ->where('signer_user_id', $this->panelist1->id)
            ->where('academic_action', 'evaluate')
            ->exists());
    }

    public function test_signature_on_res037_finalizes_round_and_facilitator_can_release(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;
        $payload = [
            'research_quality_score' => 90,
            'originality_score' => 90,
            'relevance_score' => 90,
            'general_comments' => 'Approved.',
            'student_scores' => [
                $this->student1->id => ['communication_score' => 90, 'organization_score' => 90, 'effectiveness_score' => 90],
                $this->student2->id => ['communication_score' => 90, 'organization_score' => 90, 'effectiveness_score' => 90],
            ],
        ];

        $submitAction->handle($this->panelist1, $round, $payload);
        $submitAction->handle($this->panelist2, $round, $payload);
        $submitAction->handle($this->panelist3, $round, $payload);

        $freshRound = $round->fresh();
        $this->assertEquals('complete', $freshRound->status);

        $res037 = OfficialFormInstance::where('source_type', DefenseEvaluationRound::class)
            ->where('source_id', $round->id)
            ->firstOrFail();

        // Register digital signature for designated signer (panelist1)
        UserSignature::create([
            'user_id' => $this->panelist1->id,
            'storage_disk' => 'local',
            'storage_path' => 'signatures/test.png',
            'original_filename' => 'test.png',
            'content_sha256' => hash('sha256', 'fake-signature-content'),
            'file_size' => 100,
            'mime_type' => 'image/png',
            'registered_at' => now(),
        ]);
        Storage::disk('local')->put('signatures/test.png', 'fake-signature-content');

        // Apply signature on RES-037
        $signerAction = app(ApplyOfficialFormSignature::class);
        $signerAction->handle(
            $this->panelist1,
            $res037->id,
            $res037->current_version_id,
            'sign'
        );

        $finalizedRound = $round->fresh();
        $this->assertEquals('finalized', $finalizedRound->status);
        $this->assertNotNull($finalizedRound->finalized_at);

        // Facilitator Releases Results
        $releaseAction = new ReleaseDefenseEvaluationResults;
        $releasedRound = $releaseAction->handle($this->facilitator, $finalizedRound);

        $this->assertEquals('released', $releasedRound->status);
        $this->assertNotNull($releasedRound->released_at);
        $this->assertEquals($this->facilitator->id, $releasedRound->released_by);
    }
}

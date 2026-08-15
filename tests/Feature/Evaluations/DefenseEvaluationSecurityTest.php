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
use App\Modules\DefenseScheduling\Actions\AssignDefensePanel;
use App\Modules\DefenseScheduling\Actions\CancelDefense;
use App\Modules\DefenseScheduling\Actions\RescheduleDefense;
use App\Modules\Evaluations\Actions\OpenDefenseEvaluationRound;
use App\Modules\Evaluations\Actions\SaveDefenseEvaluationDraft;
use App\Modules\Evaluations\Actions\SubmitDefenseEvaluation;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class DefenseEvaluationSecurityTest extends TestCase
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

        $this->student2 = User::factory()->create([
            'user_type' => 'student',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->student2->assignRole('student-researcher');

        $this->panelist1 = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->panelist1->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate', 'forms.res-037.sign']);

        $this->panelist2 = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->panelist2->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate', 'forms.res-037.sign']);

        $this->panelist3 = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $this->panelist3->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate', 'forms.res-037.sign']);

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

        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $this->panelist1->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);
        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $this->panelist2->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);
        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $this->panelist3->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);
    }

    public function test_submitted_evaluations_are_immutable(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;
        $payload = [
            'research_quality_score' => 85,
            'originality_score' => 85,
            'relevance_score' => 85,
            'general_comments' => 'First submission.',
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
                $this->student2->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
            ],
        ];

        $submitAction->handle($this->panelist1, $round, $payload);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Evaluation has already been submitted and is immutable.');

        $submitAction->handle($this->panelist1, $round, $payload);
    }

    public function test_student_receives_only_released_data_and_cannot_see_peer_scores_or_panelist_names(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $query = new GetEvaluationRoundData;

        // Round is 'open': student gets empty list
        $student1Data = $query->forStudent($this->student1);
        $this->assertEmpty($student1Data['rounds']);

        // Complete and release round
        $submitAction = new SubmitDefenseEvaluation;
        $payload = fn ($s1Score, $s2Score) => [
            'research_quality_score' => 90,
            'originality_score' => 90,
            'relevance_score' => 90,
            'general_comments' => 'Great defense.',
            'student_scores' => [
                $this->student1->id => ['communication_score' => $s1Score, 'organization_score' => $s1Score, 'effectiveness_score' => $s1Score],
                $this->student2->id => ['communication_score' => $s2Score, 'organization_score' => $s2Score, 'effectiveness_score' => $s2Score],
            ],
        ];

        $submitAction->handle($this->panelist1, $round, $payload(80, 95));
        $submitAction->handle($this->panelist2, $round, $payload(80, 95));
        $submitAction->handle($this->panelist3, $round, $payload(80, 95));

        // Manually mark status released for data test
        $round->update(['status' => 'released', 'released_at' => now()]);

        // Student 1 data
        $s1Rounds = $query->forStudent($this->student1)['rounds'];
        $this->assertCount(1, $s1Rounds);
        $this->assertEquals(90.0, $s1Rounds[0]['research_paper_average']);
        $this->assertEquals(80.0, $s1Rounds[0]['own_presentation_average']);
        $this->assertArrayNotHasKey('student_summaries', $s1Rounds[0]);
        $this->assertArrayNotHasKey('panelists', $s1Rounds[0]);

        // Student 2 data
        $s2Rounds = $query->forStudent($this->student2)['rounds'];
        $this->assertCount(1, $s2Rounds);
        $this->assertEquals(90.0, $s2Rounds[0]['research_paper_average']);
        $this->assertEquals(95.0, $s2Rounds[0]['own_presentation_average']);
    }

    public function test_defense_scheduling_mutations_are_blocked_once_evaluation_round_exists(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $newRoom = DefenseRoom::create(['code' => 'RM-102', 'name' => 'Conference Room B', 'is_active' => true]);

        // 1. Reschedule blocked
        $rescheduleAction = new RescheduleDefense;
        try {
            $rescheduleAction->handle(
                $this->facilitator,
                $this->defense,
                $this->defense->current_schedule_id,
                $newRoom->id,
                now()->addDays(2),
                now()->addDays(2)->addHours(2),
                'Reschedule test'
            );
            $this->fail('Reschedule did not throw exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('evaluation round exists', $e->getMessage());
        }

        // 2. Cancel blocked
        $cancelAction = new CancelDefense;
        try {
            $cancelAction->handle(
                $this->facilitator,
                $this->defense,
                $this->defense->current_schedule_id,
                'Cancellation test'
            );
            $this->fail('Cancel did not throw exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('evaluation round exists', $e->getMessage());
        }

        // 3. Panel assign blocked
        $panelAction = new AssignDefensePanel;
        try {
            $newPanelist = User::factory()->create(['user_type' => 'faculty', 'status' => 'active', 'approved_at' => now(), 'email_verified_at' => now()]);
            $panelAction->handle(
                $this->facilitator,
                $this->defense,
                [$this->panelist1->id, $this->panelist2->id, $newPanelist->id]
            );
            $this->fail('Assign panel did not throw exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('evaluation round is active', $e->getMessage());
        }
    }

    public function test_draft_rejects_unknown_student_id(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $draftAction = new SaveDefenseEvaluationDraft;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown student ID [999] in draft evaluation scores.');

        $draftAction->handle($this->panelist1, $round, [
            'research_quality_score' => 80,
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
                999 => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
            ],
        ]);
    }

    public function test_submit_rejects_unknown_student_id(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown student ID [999] in submitted evaluation scores.');

        $submitAction->handle($this->panelist1, $round, [
            'research_quality_score' => 85,
            'originality_score' => 85,
            'relevance_score' => 85,
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
                $this->student2->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
                999 => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
            ],
        ]);
    }

    public function test_submit_requires_exact_frozen_student_roster(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Missing evaluation scores for student #{$this->student2->id}.");

        $submitAction->handle($this->panelist1, $round, [
            'research_quality_score' => 85,
            'originality_score' => 85,
            'relevance_score' => 85,
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
            ],
        ]);
    }

    public function test_submit_rejects_prohibited_overposting_fields(): void
    {
        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prohibited field [research_paper_total] in evaluation submission payload.');

        $submitAction->handle($this->panelist1, $round, [
            'research_quality_score' => 85,
            'originality_score' => 85,
            'relevance_score' => 85,
            'research_paper_total' => 99.99,
            'student_scores' => [
                $this->student1->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
                $this->student2->id => ['communication_score' => 80, 'organization_score' => 80, 'effectiveness_score' => 80],
            ],
        ]);
    }

    public function test_custom_role_faculty_can_evaluate_without_canonical_role_name(): void
    {
        // Custom Faculty user with explicit permissions, NO canonical panel-member role
        $customPanelist = User::factory()->create([
            'user_type' => 'faculty',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);
        $customPanelist->givePermissionTo(['evaluations.create', 'forms.res-036.evaluate']);

        // Replace panelist3 with customPanelist in defense panel assignments
        DefensePanelAssignment::where('defense_id', $this->defense->id)->where('user_id', $this->panelist3->id)->delete();
        DefensePanelAssignment::create(['defense_id' => $this->defense->id, 'user_id' => $customPanelist->id, 'assigned_by' => $this->facilitator->id, 'assigned_at' => now()]);

        $openAction = new OpenDefenseEvaluationRound;
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;
        $eval = $submitAction->handle($customPanelist, $round, [
            'research_quality_score' => 90,
            'originality_score' => 90,
            'relevance_score' => 90,
            'student_scores' => [
                $this->student1->id => ['communication_score' => 90, 'organization_score' => 90, 'effectiveness_score' => 90],
                $this->student2->id => ['communication_score' => 90, 'organization_score' => 90, 'effectiveness_score' => 90],
            ],
        ]);

        $this->assertEquals('submitted', $eval->status);
        $this->assertEquals($customPanelist->id, $eval->panelist_user_id);
    }

    public function test_panelist_signer_permission_differentiation(): void
    {
        // P1 has evaluate + sign, P2 has evaluate ONLY, P3 has evaluate ONLY
        $this->panelist2->revokePermissionTo('forms.res-037.sign');
        $this->panelist3->revokePermissionTo('forms.res-037.sign');

        $openAction = new OpenDefenseEvaluationRound;
        // Designate P1 as signer
        $round = $openAction->handle($this->facilitator, $this->defense, $this->panelist1->id);

        $submitAction = new SubmitDefenseEvaluation;
        $payload = [
            'research_quality_score' => 90,
            'originality_score' => 90,
            'relevance_score' => 90,
            'student_scores' => [
                $this->student1->id => ['communication_score' => 90, 'organization_score' => 90, 'effectiveness_score' => 90],
                $this->student2->id => ['communication_score' => 90, 'organization_score' => 90, 'effectiveness_score' => 90],
            ],
        ];

        // All three submit successfully
        $submitAction->handle($this->panelist1, $round, $payload);
        $submitAction->handle($this->panelist2, $round, $payload);
        $submitAction->handle($this->panelist3, $round, $payload);

        $round->refresh();
        $this->assertEquals('complete', $round->status);

        // P2 tries to sign RES-037 -> DENIED
        $res037 = OfficialFormInstance::where('source_type', DefenseEvaluationRound::class)->where('source_id', $round->id)->first();
        UserSignature::create([
            'user_id' => $this->panelist2->id,
            'storage_disk' => 'local',
            'storage_path' => 'signatures/p2.png',
            'original_filename' => 'p2.png',
            'content_sha256' => hash('sha256', 'p2-signature'),
            'file_size' => 100,
            'mime_type' => 'image/png',
            'registered_at' => now(),
        ]);
        Storage::disk('local')->put('signatures/p2.png', 'p2-signature');

        $applySig = app(ApplyOfficialFormSignature::class);
        try {
            $applySig->handle($this->panelist2, $res037->id, $res037->current_version_id, 'sign');
            $this->fail('P2 signature on RES-037 did not throw exception.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not authorized', $e->getMessage());
        }
    }

    public function test_admin_denied_academic_evaluations(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $openAction = new OpenDefenseEvaluationRound;

        $this->expectException(AuthorizationException::class);
        $openAction->handle($admin, $this->defense);
    }
}

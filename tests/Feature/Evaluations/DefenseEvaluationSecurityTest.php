<?php

namespace Tests\Feature\Evaluations;

use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\AssignDefensePanel;
use App\Modules\DefenseScheduling\Actions\CancelDefense;
use App\Modules\DefenseScheduling\Actions\RescheduleDefense;
use App\Modules\Evaluations\Actions\OpenDefenseEvaluationRound;
use App\Modules\Evaluations\Actions\SubmitDefenseEvaluation;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
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
}

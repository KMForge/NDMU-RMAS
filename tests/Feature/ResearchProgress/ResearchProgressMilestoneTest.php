<?php

namespace Tests\Feature\ResearchProgress;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\ResearchMilestoneStatus;
use App\Models\Defense;
use App\Models\Document;
use App\Models\MilestoneDefinition;
use App\Models\Program;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\ResearchClassGroupMemberHistory;
use App\Models\ResearchGroupMilestone;
use App\Models\ResearchGroupMilestoneEvent;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Classes\Actions\CreateResearchClassGroup;
use App\Modules\Research\Queries\GetStudentDashboardData;
use App\Modules\ResearchProgress\Actions\SyncResearchMilestoneDefinitions;
use App\Modules\ResearchProgress\Queries\GetFacilitatorProgressData;
use App\Modules\ResearchProgress\Queries\GetResearchGroupProgress;
use App\Modules\ResearchProgress\Services\ResearchJourneyService;
use App\Notifications\AcademicWorkflowNotification;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class ResearchProgressMilestoneTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $adviser;

    private User $student;

    private ResearchClass $researchClass;

    private ResearchClassGroup $group;

    private ResearchClassEnrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        app(SyncResearchMilestoneDefinitions::class)->execute();

        $this->facilitator = $this->user('research-facilitator');
        $this->adviser = $this->user('thesis-adviser');
        $this->student = $this->user('student');
        $this->researchClass = new ResearchClass([
            'facilitator_id' => $this->facilitator->getKey(), 'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone II', 'max_students' => 50, 'is_active' => true,
        ]);
        $this->researchClass->setJoinCode('PROGRESS1');
        $this->researchClass->save();
        $this->group = ResearchClassGroup::query()->create([
            'research_class_id' => $this->researchClass->getKey(), 'creation_token' => (string) Str::uuid(),
            'name' => 'Group Progress', 'adviser_id' => $this->adviser->getKey(),
            'created_by' => $this->facilitator->getKey(), 'status' => 'active',
        ]);
        $this->enrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $this->researchClass->getKey(), 'student_id' => $this->student->getKey(),
            'status' => 'active', 'requested_at' => now(), 'joined_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $this->group->getKey(), 'research_class_id' => $this->researchClass->getKey(),
            'research_class_enrollment_id' => $this->enrollment->getKey(), 'student_id' => $this->student->getKey(),
            'assigned_by' => $this->facilitator->getKey(),
        ]);
    }

    public function test_initialization_is_idempotent_and_creates_exactly_fourteen_active_records(): void
    {
        $query = app(GetResearchGroupProgress::class);
        $this->assertCount(14, $query->for($this->group)['milestones']);
        $this->assertCount(14, $query->for($this->group)['milestones']);
        $this->assertDatabaseCount('milestone_definitions', 14);
        $this->assertDatabaseCount('research_group_milestones', 14);
    }

    public function test_new_group_created_through_the_domain_action_is_initialized_immediately(): void
    {
        $group = app(CreateResearchClassGroup::class)->handle(
            $this->facilitator,
            $this->researchClass,
            (string) Str::uuid(),
            'New Progress Group',
        );

        $this->assertSame(14, $group->milestones()->whereHas('definition', fn ($q) => $q->where('is_active', true))->count());
    }

    public function test_progress_is_derived_from_weights_and_not_a_client_percentage(): void
    {
        $milestones = $this->milestones();
        $this->startAndComplete($milestones[0]);
        $summary = app(GetResearchGroupProgress::class)->for($this->group);

        $expectedSinglePercentage = round((1 / 13) * 100, 2);
        $this->assertSame($expectedSinglePercentage, $summary['progress_percentage']);
        $this->assertSame(1, $summary['completed_count']);

        foreach ($milestones->skip(1) as $milestone) {
            $this->startAndComplete($milestone);
        }
        $this->assertSame(100.0, app(GetResearchGroupProgress::class)->for($this->group)['progress_percentage']);
    }

    public function test_facilitator_monitoring_uses_the_same_authoritative_journey_percentage_as_the_student_dashboard(): void
    {
        app(GetResearchGroupProgress::class)->for($this->group);

        $stages = collect(range(1, 14))->mapWithKeys(fn (int $stage): array => [
            $stage => ['is_completed' => $stage <= 4, 'is_optional' => $stage === 13],
        ])->all();

        $this->mock(ResearchJourneyService::class, function (MockInterface $mock) use ($stages): void {
            $mock->shouldReceive('getJourneyForGroup')
                ->once()
                ->withArgs(fn (ResearchClassGroup $group, User $actor): bool => $group->is($this->group) && $actor->is($this->facilitator))
                ->andReturn([
                    'percentage' => 31,
                    'current_stage' => 5,
                    'current_stage_name' => 'Validation of Survey Instrument',
                    'stages' => $stages,
                ]);
        });

        $group = app(GetFacilitatorProgressData::class)->for($this->facilitator)['progressGroups']->first();

        $this->assertSame(31, $group->progress_summary['progress_percentage']);
        $this->assertSame(4, $group->progress_summary['completed_count']);
        $this->assertSame(13, $group->progress_summary['applicable_count']);
        $this->assertSame(5, $group->progress_summary['journey']['current_stage']);
    }

    public function test_language_and_technical_editing_is_optional_and_does_not_block_final_submission(): void
    {
        $milestones = $this->milestones();

        $milestones
            ->reject(fn (ResearchGroupMilestone $milestone): bool => in_array($milestone->definition->code, [
                'language-technical-editing',
                'submission-final-research-paper',
            ], true))
            ->each(fn (ResearchGroupMilestone $milestone) => $milestone->update([
                'status' => ResearchMilestoneStatus::Completed,
                'completed_at' => now(),
                'completed_by' => $this->facilitator->getKey(),
            ]));

        $finalSubmission = $milestones->firstWhere('definition.code', 'submission-final-research-paper');
        $this->actingAs($this->facilitator)
            ->patchJson(route('facilitator.progress.start', $finalSubmission))
            ->assertOk();
        $this->actingAs($this->facilitator)
            ->patchJson(route('facilitator.progress.complete', $finalSubmission))
            ->assertOk();

        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);
        $summary = app(GetResearchGroupProgress::class)->for($this->group);

        $this->assertTrue($journey['stages'][13]['is_optional']);
        $this->assertFalse($journey['stages'][13]['is_completed']);
        $this->assertTrue($journey['stages'][14]['is_completed']);
        $this->assertSame(100, $journey['percentage']);
        $this->assertSame(13, $journey['required_stage_count']);
        $this->assertSame(13, $summary['applicable_count']);
        $this->assertSame(100.0, $summary['progress_percentage']);
    }

    public function test_bsit_journey_automatically_completes_program_exempt_stages(): void
    {
        $this->seed(AcademicStructureSeeder::class);
        $program = Program::query()->where('code', 'BSIT')->firstOrFail();

        StudentProfile::query()->updateOrCreate(
            ['user_id' => $this->student->getKey()],
            [
                'program_id' => $program->getKey(),
                'student_number' => 'BSIT-PROGRESS-001',
                'year_level' => '4th',
            ],
        );

        $this->milestones()
            ->filter(fn (ResearchGroupMilestone $milestone): bool => $milestone->definition->sequence <= 4)
            ->each(fn (ResearchGroupMilestone $milestone) => $milestone->update([
                'status' => ResearchMilestoneStatus::Completed,
                'completed_at' => now(),
                'completed_by' => $this->facilitator->getKey(),
            ]));

        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);

        foreach ([5, 7, 8, 9, 13] as $stageNumber) {
            $this->assertTrue($journey['stages'][$stageNumber]['is_auto_completed']);
            $this->assertTrue($journey['stages'][$stageNumber]['is_completed']);
            $this->assertEmpty($journey['stages'][$stageNumber]['pending_requirements']);
        }

        $this->assertSame(6, $journey['current_stage']);
        $this->assertSame('Submission of Complete Research Proposal Paper', $journey['current_stage_name']);
        $this->assertSame(62, $journey['percentage']);
        $this->assertNotSame('res-042', $journey['next_action']['form_code'] ?? null);
        $this->assertSame('Upload Complete Research Proposal Paper', $journey['next_action']['label']);
        $this->assertSame('document', $journey['next_action']['action_type']);
        $this->assertSame('Complete Research Proposal Paper', $journey['next_action']['document_label']);
        $this->assertSame(route('student.dashboard', ['tab' => 'proposal']), $journey['next_action']['route']);

        $this->travel(1)->second();
        $this->document($this->group)->update(['document_stage' => DocumentStage::ProposalDefense]);

        $journeyAfterSubmission = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);

        $this->assertTrue($journeyAfterSubmission['stages'][6]['is_completed']);
        $this->assertSame(10, $journeyAfterSubmission['current_stage']);
        $this->assertSame('Research Pre-Final Defense', $journeyAfterSubmission['current_stage_name']);
        $this->assertSame(69, $journeyAfterSubmission['percentage']);
        $this->assertNotNull($journeyAfterSubmission['next_action']);
    }

    public function test_order_is_enforced_and_controlled_override_requires_reason(): void
    {
        $second = $this->milestones()[1];
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $second))->assertUnprocessable();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $second), ['override_order' => true])->assertUnprocessable();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $second), [
            'override_order' => true, 'reason' => 'Approved exception for parallel institutional review.',
        ])->assertOk();

        $this->assertDatabaseHas('research_group_milestone_events', [
            'research_group_milestone_id' => $second->getKey(), 'override_order' => true,
        ]);
    }

    public function test_proposal_defense_sequence_requires_proposal_defense_before_survey_validation_and_data_gathering(): void
    {
        $milestones = $this->milestones();
        $proposalDefense = $milestones->firstWhere('definition.code', 'research-proposal-defense');
        $surveyValidation = $milestones->firstWhere('definition.code', 'validation-survey-instrument');
        $dataGathering = $milestones->firstWhere('definition.code', 'data-gathering');

        $this->assertTrue($proposalDefense->definition->sequence < $surveyValidation->definition->sequence);
        $this->assertTrue($surveyValidation->definition->sequence < $dataGathering->definition->sequence);

        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $dataGathering))->assertUnprocessable();
    }

    public function test_pre_final_defense_is_a_distinct_required_stage_before_final_oral_defense(): void
    {
        $milestones = $this->milestones();
        $preFinalDefense = $milestones->firstWhere('definition.code', 'research-pre-final-defense');
        $finalDefense = $milestones->firstWhere('definition.code', 'research-final-oral-defense');

        $this->assertNotNull($preFinalDefense);
        $this->assertNotNull($finalDefense);
        $this->assertSame(10, $preFinalDefense->definition->sequence);
        $this->assertSame(11, $finalDefense->definition->sequence);
        $this->assertTrue($preFinalDefense->definition->sequence < $finalDefense->definition->sequence);
    }

    public function test_final_defense_progress_is_monotonic_and_consistent_in_both_student_trackers(): void
    {
        $this->milestones();

        $defense = Defense::query()->create([
            'research_class_group_id' => $this->group->getKey(),
            'defense_type' => 'final_defense',
            'status' => 'scheduled',
            'created_by' => $this->facilitator->getKey(),
        ]);

        $journey = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);
        $dashboard = app(GetStudentDashboardData::class)->for($this->student, activeTab: 'progress');
        $displayMilestones = $dashboard['researchMilestones']->keyBy('sequence');

        foreach (range(1, 10) as $stageNumber) {
            $this->assertTrue($journey['stages'][$stageNumber]['is_completed']);
            $this->assertSame('completed', $displayMilestones[$stageNumber]->status);
        }

        $this->assertSame(11, $journey['current_stage']);
        $this->assertFalse($journey['stages'][11]['is_completed']);
        $this->assertSame('in_progress', $displayMilestones[11]->status);
        $this->assertSame($journey['percentage'], $dashboard['dashboardOverview']['progress_percentage']);
        $this->assertSame(10, $dashboard['dashboardOverview']['completed_milestones']);

        $defense->update(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $this->facilitator->getKey()]);

        $completedJourney = app(ResearchJourneyService::class)->getJourneyForGroup($this->group->fresh(), $this->student);
        $completedDashboard = app(GetStudentDashboardData::class)->for($this->student, activeTab: 'progress');

        $this->assertTrue($completedJourney['stages'][11]['is_completed']);
        $this->assertSame(12, $completedJourney['current_stage']);
        $this->assertSame('completed', $completedDashboard['researchMilestones']->keyBy('sequence')[11]->status);
        $this->assertSame(11, $completedDashboard['dashboardOverview']['completed_milestones']);
    }

    public function test_proposal_revision_and_whole_paper_revision_are_independent_milestones(): void
    {
        $milestones = $this->milestones();
        $proposalRevision = $milestones->firstWhere('definition.code', 'revision-research-proposal');
        $wholePaperRevision = $milestones->firstWhere('definition.code', 'revision-whole-research-paper');

        $this->assertNotNull($proposalRevision);
        $this->assertNotNull($wholePaperRevision);
        $this->assertNotEquals($proposalRevision->definition->getKey(), $wholePaperRevision->definition->getKey());
    }

    public function test_not_applicable_excludes_weight_and_requires_a_reason(): void
    {
        $last = $this->milestones()->last();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.not-applicable', $last))->assertUnprocessable();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.not-applicable', $last), [
            'reason' => 'The approved study does not require this institutional step.',
        ])->assertOk();

        $summary = app(GetResearchGroupProgress::class)->for($this->group);
        $this->assertSame(12, $summary['applicable_count']);
        $this->assertSame(0.0, $summary['progress_percentage']);
    }

    public function test_not_applicable_milestone_can_be_re_enabled_with_audited_reason(): void
    {
        $last = $this->milestones()->last();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.not-applicable', $last), [
            'reason' => 'The institutional exception initially applied.',
        ])->assertOk();

        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.correct', $last), [
            'status' => 'pending',
        ])->assertUnprocessable();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.correct', $last), [
            'status' => 'pending', 'reason' => 'The exception was withdrawn after academic review.',
        ])->assertOk();

        $this->assertSame(ResearchMilestoneStatus::Pending, $last->fresh()->status);
        $this->assertDatabaseHas('research_group_milestone_events', [
            'research_group_milestone_id' => $last->getKey(), 'event' => 'status_corrected',
            'from_status' => 'not_applicable', 'to_status' => 'pending',
        ]);
    }

    public function test_completed_correction_preserves_history_and_recalculates_progress(): void
    {
        $first = $this->milestones()->first();
        $this->startAndComplete($first);
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.correct', $first), [
            'status' => 'in_progress', 'reason' => 'Completion was recorded before the signed approval arrived.',
        ])->assertOk();

        $this->assertSame(ResearchMilestoneStatus::InProgress, $first->fresh()->status);
        $this->assertSame(0.0, app(GetResearchGroupProgress::class)->for($this->group)['progress_percentage']);
        $this->assertDatabaseHas('research_group_milestone_events', ['research_group_milestone_id' => $first->getKey(), 'event' => 'status_corrected']);
    }

    public function test_due_date_changes_are_audited_and_overdue_is_derived(): void
    {
        $first = $this->milestones()->first();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.due-date', $first), [
            'due_at' => now()->subDay()->toDateString(), 'reason' => 'Academic calendar adjustment.',
        ])->assertOk();
        $this->assertTrue($first->fresh()->isOverdue());
        $this->assertDatabaseHas('research_group_milestone_events', ['research_group_milestone_id' => $first->getKey(), 'event' => 'due_date_changed']);
        $this->startAndComplete($first);
        $this->assertFalse($first->fresh()->isOverdue());
    }

    public function test_students_share_read_only_group_progress_and_idor_is_denied(): void
    {
        $this->milestones();
        $this->actingAs($this->student)->getJson(route('student.progress.show', $this->group))->assertOk();
        $outside = $this->user('student');
        $this->actingAs($outside)->getJson(route('student.progress.show', $this->group))->assertForbidden();
        $this->actingAs($this->student)->patchJson(route('facilitator.progress.start', $this->milestones()->first()))->assertForbidden();
    }

    public function test_all_current_group_members_receive_the_same_group_progress(): void
    {
        $peer = $this->user('student');
        $peerEnrollment = ResearchClassEnrollment::query()->create([
            'research_class_id' => $this->researchClass->getKey(), 'student_id' => $peer->getKey(),
            'status' => 'active', 'requested_at' => now(), 'joined_at' => now(),
        ]);
        ResearchClassGroupMember::query()->create([
            'research_class_group_id' => $this->group->getKey(), 'research_class_id' => $this->researchClass->getKey(),
            'research_class_enrollment_id' => $peerEnrollment->getKey(), 'student_id' => $peer->getKey(),
            'assigned_by' => $this->facilitator->getKey(),
        ]);
        $this->startAndComplete($this->milestones()->first());

        $studentData = $this->actingAs($this->student)->getJson(route('student.progress.show', $this->group))->assertOk()->json();
        $peerData = $this->actingAs($peer)->getJson(route('student.progress.show', $this->group))->assertOk()->json();

        $this->assertSame($studentData['progress_percentage'], $peerData['progress_percentage']);
        $this->assertSame($studentData['milestones'], $peerData['milestones']);
    }

    public function test_verified_former_member_retains_historical_read_only_access(): void
    {
        $this->milestones();
        ResearchClassGroupMemberHistory::query()->create([
            'research_class_group_id' => $this->group->getKey(), 'research_class_id' => $this->researchClass->getKey(),
            'research_class_enrollment_id' => $this->enrollment->getKey(), 'student_id' => $this->student->getKey(),
            'assigned_by' => $this->facilitator->getKey(), 'joined_at' => now()->subMonth(), 'archived_at' => now(),
        ]);
        ResearchClassGroupMember::query()->delete();
        $this->group->update(['status' => 'disbanded', 'disbanded_at' => now()]);

        $this->actingAs($this->student)->getJson(route('student.progress.show', $this->group))->assertOk();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $this->milestones()->first()))->assertForbidden();
    }

    public function test_only_current_adviser_has_read_only_assigned_access(): void
    {
        $this->milestones();
        $this->actingAs($this->adviser)->getJson(route('adviser.progress.show', $this->group))->assertOk();
        $other = $this->user('thesis-adviser');
        $this->actingAs($other)->getJson(route('adviser.progress.show', $this->group))->assertForbidden();
        $this->actingAs($this->adviser)->patchJson(route('facilitator.progress.start', $this->milestones()->first()))->assertForbidden();
        $this->actingAs($this->adviser)->patchJson(route('facilitator.progress.due-date', $this->milestones()->first()), [
            'due_at' => now()->addWeek()->toDateString(),
        ])->assertForbidden();
    }

    public function test_only_owning_facilitator_can_manage_even_with_same_role(): void
    {
        $first = $this->milestones()->first();
        $other = $this->user('research-facilitator');
        $this->actingAs($other)->patchJson(route('facilitator.progress.start', $first))->assertForbidden();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $first))->assertOk();
    }

    public function test_multi_role_faculty_only_manages_progress_for_an_owned_class(): void
    {
        $this->facilitator->assignRole('thesis-adviser');
        $otherFacilitator = $this->user('research-facilitator');
        $otherClass = new ResearchClass([
            'facilitator_id' => $otherFacilitator->getKey(), 'creation_token' => (string) Str::uuid(),
            'name' => 'Other Class', 'max_students' => 50, 'is_active' => true,
        ]);
        $otherClass->setJoinCode('OTHER001');
        $otherClass->save();
        $otherGroup = ResearchClassGroup::query()->create([
            'research_class_id' => $otherClass->getKey(), 'creation_token' => (string) Str::uuid(),
            'name' => 'Other Group', 'adviser_id' => $this->facilitator->getKey(),
            'created_by' => $otherFacilitator->getKey(), 'status' => 'active',
        ]);
        $otherFirst = app(GetResearchGroupProgress::class)->for($otherGroup)['milestones']->first();

        $this->actingAs($this->facilitator)->getJson(route('adviser.progress.show', $otherGroup))->assertOk();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $otherFirst))->assertForbidden();
    }

    public function test_client_percentage_is_ignored_and_phase_actions_notify_exact_group_context(): void
    {
        Notification::fake();
        $first = $this->milestones()->first();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $first), [
            'progress_percentage' => 99,
        ])->assertOk();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.complete', $first), [
            'progress_percentage' => 99,
        ])->assertOk();

        $expectedSinglePercentage = round((1 / 13) * 100, 2);
        $this->assertSame($expectedSinglePercentage, app(GetResearchGroupProgress::class)->for($this->group)['progress_percentage']);
        Notification::assertSentTo(
            [$this->student, $this->adviser],
            AcademicWorkflowNotification::class,
            fn (AcademicWorkflowNotification $notification): bool => $notification->eventKey === 'research.milestone.updated',
        );
        Notification::assertNotSentTo($this->facilitator, AcademicWorkflowNotification::class);
    }

    public function test_evidence_must_belong_to_the_same_group(): void
    {
        $first = $this->milestones()->first();
        $valid = $this->document($this->group);
        $otherGroup = ResearchClassGroup::query()->create([
            'research_class_id' => $this->researchClass->getKey(), 'creation_token' => (string) Str::uuid(),
            'name' => 'Other Group', 'created_by' => $this->facilitator->getKey(), 'status' => 'active',
        ]);
        $invalid = $this->document($otherGroup);

        $this->actingAs($this->facilitator)->postJson(route('facilitator.progress.evidence', $first), [
            'evidence_type' => 'document', 'evidence_id' => $valid->getKey(),
        ])->assertOk();
        $this->actingAs($this->facilitator)->postJson(route('facilitator.progress.evidence', $first), [
            'evidence_type' => 'document', 'evidence_id' => $invalid->getKey(),
        ])->assertUnprocessable();
    }

    public function test_accepted_document_does_not_automatically_complete_a_milestone(): void
    {
        $this->milestones();
        $this->document($this->group, DocumentStatus::Accepted);
        $this->assertSame(0, ResearchGroupMilestone::query()->where('status', 'completed')->count());
    }

    public function test_legacy_group_milestones_with_academic_history_are_preserved_intact_under_inactive_definitions(): void
    {
        $legacyDef = MilestoneDefinition::query()->create([
            'code' => 'legacy-custom-phase',
            'name' => 'Old Legacy Phase',
            'sequence' => 999,
            'weight' => 1,
            'is_active' => false,
        ]);

        $legacyGroupMilestone = ResearchGroupMilestone::query()->create([
            'research_class_group_id' => $this->group->getKey(),
            'milestone_definition_id' => $legacyDef->getKey(),
            'status' => ResearchMilestoneStatus::Completed,
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(2),
            'remarks' => 'Completed under old academic process.',
        ]);

        ResearchGroupMilestoneEvent::query()->create([
            'research_group_milestone_id' => $legacyGroupMilestone->getKey(),
            'actor_id' => $this->facilitator->getKey(),
            'event' => 'status_changed',
            'from_status' => 'pending',
            'to_status' => 'completed',
            'occurred_at' => now()->subDays(2),
        ]);

        app(SyncResearchMilestoneDefinitions::class)->execute();

        $this->assertDatabaseHas('research_group_milestones', [
            'id' => $legacyGroupMilestone->getKey(),
            'status' => 'completed',
            'remarks' => 'Completed under old academic process.',
        ]);

        $summary = app(GetResearchGroupProgress::class)->for($this->group);
        $this->assertCount(14, $summary['milestones']);
        $this->assertSame(0.0, $summary['progress_percentage']);
    }

    private function milestones()
    {
        return app(GetResearchGroupProgress::class)->for($this->group)['milestones'];
    }

    private function startAndComplete(ResearchGroupMilestone $milestone): void
    {
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.start', $milestone))->assertOk();
        $this->actingAs($this->facilitator)->patchJson(route('facilitator.progress.complete', $milestone))->assertOk();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active', 'approved_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function document(ResearchClassGroup $group, DocumentStatus $status = DocumentStatus::Pending): Document
    {
        return Document::query()->forceCreate([
            'user_id' => $this->student->getKey(), 'research_class_group_id' => $group->getKey(),
            'submission_token' => (string) Str::uuid(), 'original_filename' => Str::uuid().'.pdf',
            'stored_filename' => Str::uuid().'.pdf', 'file_type' => 'pdf', 'mime_type' => 'application/pdf',
            'version_number' => 1, 'is_current' => true, 'file_size' => 100,
            'storage_disk' => 'local', 'storage_path' => 'private/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', Str::uuid()), 'submitted_at' => now(), 'status' => $status,
        ]);
    }
}

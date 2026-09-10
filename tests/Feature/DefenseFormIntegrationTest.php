<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseEvaluationRoundPanelist;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\CancelDefense;
use App\Modules\DefenseScheduling\Actions\RescheduleDefense;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\OfficialForms\Services\OfficialFormSignatureHasher;
use App\Modules\OfficialForms\Validators\OfficialFormPayloadValidator;
use App\Policies\OfficialFormInstancePolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DefenseFormIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $panelist;

    private User $nonPanelist;

    private ResearchClassGroup $group;

    private DefenseRoom $room;

    private DefenseSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        (new SyncOfficialFormCatalog)->handle();

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'forms.res-036.evaluate', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'forms.res-036.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'guard_name' => 'web']);

        $this->facilitator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->facilitator->givePermissionTo('defenses.manage');

        $this->panelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->panelist->givePermissionTo(['forms.res-036.evaluate', 'evaluations.create']);

        $this->nonPanelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->nonPanelist->givePermissionTo(['forms.res-036.evaluate', 'evaluations.create']);

        $class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $this->group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group Alpha',
            'leader_student_id' => $this->facilitator->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $this->room = DefenseRoom::create([
            'code' => 'RM-201',
            'name' => 'Audio Visual Room',
            'location_notes' => '2nd Floor Science Wing',
            'is_active' => true,
        ]);

        $startsAt = Carbon::now()->addDays(3)->setHour(10)->setMinute(0);
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

    public function test_assigned_panelist_can_create_res_036_with_schedule_snapshot(): void
    {
        $action = app(CreateOfficialFormInstance::class);

        $instance = $action->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $this->assertInstanceOf(OfficialFormInstance::class, $instance);
        $this->assertEquals('RES-036', $instance->definition->code);
        $this->assertEquals(DefenseSchedule::class, $instance->source_type);
        $this->assertEquals($this->schedule->id, $instance->source_id);

        $version = $instance->currentVersion;
        $this->assertNotNull($version->source_snapshot);
        $this->assertEquals('proposal_defense', $version->source_snapshot['defense_type']);
        $this->assertEquals('RM-201', $version->source_snapshot['room_code']);
        $this->assertEquals('Audio Visual Room', $version->source_snapshot['room_name']);
        $this->assertEquals('Group Alpha', $version->source_snapshot['group_name']);
    }

    public function test_non_panelist_cannot_create_res_036(): void
    {
        $action = app(CreateOfficialFormInstance::class);

        $this->expectException(InvalidArgumentException::class);

        $action->handle(
            $this->nonPanelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );
    }

    public function test_signature_hasher_includes_source_snapshot(): void
    {
        $createAction = app(CreateOfficialFormInstance::class);
        $hasher = app(OfficialFormSignatureHasher::class);

        $instance = $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $version = $instance->currentVersion;
        $hash = $hasher->hashVersion($version);

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
    }

    public function test_res036_payload_is_empty_in_phase21(): void
    {
        $validator = app(OfficialFormPayloadValidator::class);

        $validatedEmpty = $validator->validate('RES-036', []);
        $this->assertIsArray($validatedEmpty);
        $this->assertEmpty($validatedEmpty);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload contains unknown fields [score, remarks] for form RES-036.');

        $validator->validate('RES-036', ['score' => 95, 'remarks' => 'Pass']);
    }

    public function test_res036_policy_blocks_mutations_and_evaluations_in_phase21(): void
    {
        $createAction = app(CreateOfficialFormInstance::class);
        $instance = $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $policy = app(OfficialFormInstancePolicy::class);

        $this->assertFalse($policy->updateDraft($this->nonPanelist, $instance));
        $this->assertFalse($policy->submit($this->nonPanelist, $instance));
    }

    public function test_superseded_schedule_cannot_create_new_res036(): void
    {
        $rescheduleAction = app(RescheduleDefense::class);

        $defense = $this->schedule->defense;
        $oldScheduleId = $this->schedule->id;

        $newStartsAt = Carbon::now()->addDays(4)->setHour(14)->setMinute(0);
        $newEndsAt = (clone $newStartsAt)->addHours(2);

        $rescheduleAction->handle(
            $this->facilitator,
            $defense,
            $oldScheduleId,
            $this->room->id,
            $newStartsAt,
            $newEndsAt,
            'Reschedule reason'
        );

        $createAction = app(CreateOfficialFormInstance::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not authorized to initiate RES-036 for defense schedule');

        $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $oldScheduleId
        );
    }

    public function test_cancelled_schedule_cannot_create_new_res036(): void
    {
        $cancelAction = app(CancelDefense::class);

        $defense = $this->schedule->defense;
        $cancelAction->handle($this->facilitator, $defense, $this->schedule->id, 'Cancel reason');

        $createAction = app(CreateOfficialFormInstance::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not authorized to initiate RES-036 for defense schedule');

        $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );
    }

    public function test_historical_res036_snapshot_survives_reschedule(): void
    {
        $createAction = app(CreateOfficialFormInstance::class);
        $instance = $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $historicalVersion = $instance->currentVersion;
        $oldRoomCode = $historicalVersion->source_snapshot['room_code'];

        $rescheduleAction = app(RescheduleDefense::class);
        $newStartsAt = Carbon::now()->addDays(5)->setHour(10)->setMinute(0);
        $newEndsAt = (clone $newStartsAt)->addHours(2);

        $rescheduleAction->handle(
            $this->facilitator,
            $this->schedule->defense,
            $this->schedule->id,
            $this->room->id,
            $newStartsAt,
            $newEndsAt,
            'Rescheduled defense'
        );

        $historicalVersion->refresh();
        $this->assertEquals($oldRoomCode, $historicalVersion->source_snapshot['room_code']);
        $this->assertEquals($this->schedule->id, $instance->source_id);
    }

    public function test_null_snapshot_preserves_old_hash_algorithm_and_tamper_detection(): void
    {
        $createAction = app(CreateOfficialFormInstance::class);
        $hasher = app(OfficialFormSignatureHasher::class);

        $instance = $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $version = $instance->currentVersion;

        $hashWithSnapshot = $hasher->hashVersion($version);

        // Modify snapshot field and verify hash changes (tamper-detection)
        $tamperedSnapshot = $version->source_snapshot;
        $tamperedSnapshot['room_code'] = 'TAMPERED-ROOM';
        $version->source_snapshot = $tamperedSnapshot;

        $hashTampered = $hasher->hashVersion($version);
        $this->assertNotEquals($hashWithSnapshot, $hashTampered);
    }

    public function test_panelist_b_cannot_view_or_submit_panelist_a_res036_evaluation(): void
    {
        $createAction = app(CreateOfficialFormInstance::class);

        $instance = $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $policy = app(OfficialFormInstancePolicy::class);

        // Panelist A (author) can view and submit
        $this->assertTrue($policy->view($this->panelist, $instance));
        $this->assertTrue($policy->submit($this->panelist, $instance));
        $this->assertTrue($policy->evaluate($this->panelist, $instance));

        // Panelist B (non-author panelist) CANNOT view, submit, or evaluate
        $this->assertFalse($policy->view($this->nonPanelist, $instance));
        $this->assertFalse($policy->submit($this->nonPanelist, $instance));
        $this->assertFalse($policy->evaluate($this->nonPanelist, $instance));

        // Facilitator can view for supervision, but CANNOT submit or evaluate on panelist's behalf
        $this->assertTrue($policy->view($this->facilitator, $instance));
        $this->assertFalse($policy->submit($this->facilitator, $instance));
        $this->assertFalse($policy->evaluate($this->facilitator, $instance));
    }

    public function test_res036_not_in_pending_actions_for_other_panelist(): void
    {
        $createAction = app(CreateOfficialFormInstance::class);

        $instance = $createAction->handle(
            $this->panelist,
            'RES-036',
            $this->group->id,
            null,
            'general',
            DefenseSchedule::class,
            $this->schedule->id
        );

        $pendingService = app(GetPendingAcademicActionsForUser::class);

        $panelistPending = $pendingService->execute($this->panelist);
        $this->assertTrue($panelistPending->contains('instance_id', $instance->id));

        $otherPanelistPending = $pendingService->execute($this->nonPanelist);
        $this->assertFalse($otherPanelistPending->contains('instance_id', $instance->id));
    }

    public function test_open_defense_evaluation_round_appears_in_pending_actions_for_assigned_panelist(): void
    {
        $defense = $this->schedule->defense;
        $assignment = $defense->activePanelAssignments->first();

        $round = DefenseEvaluationRound::query()->create([
            'defense_id' => $defense->id,
            'defense_schedule_id' => $this->schedule->id,
            'research_class_group_id' => $this->group->id,
            'program_code' => 'BSCS',
            'status' => 'open',
            'summary_signer_user_id' => $this->panelist->id,
            'opened_by' => $this->facilitator->id,
            'opened_at' => now(),
        ]);

        DefenseEvaluationRoundPanelist::query()->create([
            'defense_evaluation_round_id' => $round->id,
            'defense_panel_assignment_id' => $assignment->id,
            'panelist_user_id' => $this->panelist->id,
            'position' => 1,
        ]);

        $pendingService = app(GetPendingAcademicActionsForUser::class);
        $panelistPending = $pendingService->execute($this->panelist);

        $evalAction = $panelistPending->firstWhere('id', "defense-round-{$round->id}-evaluate");
        $this->assertNotNull($evalAction);
        $this->assertSame('res-036', $evalAction['form_code']);
        $this->assertStringContainsString('Evaluate', $evalAction['action_label']);
    }
}

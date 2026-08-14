<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Defense;
use App\Models\DefenseRoom;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\CancelDefense;
use App\Modules\DefenseScheduling\Actions\RescheduleDefense;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DefenseSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $panelist;

    private ResearchClass $class;

    private ResearchClassGroup $group;

    private DefenseRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.facilitator.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'guard_name' => 'web']);

        $this->facilitator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->facilitator->givePermissionTo(['defenses.manage', 'dashboards.facilitator.view']);

        $this->panelist = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->panelist->givePermissionTo('evaluations.create');

        $this->class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 1',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $this->group = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group 1',
            'leader_student_id' => $this->facilitator->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $this->room = DefenseRoom::create([
            'code' => 'RM-101',
            'name' => 'Conference Room A',
            'is_active' => true,
        ]);
    }

    public function test_facilitator_can_schedule_defense(): void
    {
        $action = app(ScheduleDefense::class);

        $startsAt = Carbon::now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $defense = $action->handle(
            $this->facilitator,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            [$this->panelist->id]
        );

        $this->assertInstanceOf(Defense::class, $defense);
        $this->assertEquals('scheduled', $defense->status);
        $this->assertEquals('proposal_defense', $defense->defense_type);
        $this->assertNotNull($defense->current_schedule_id);

        $this->assertDatabaseHas('defense_schedules', [
            'id' => $defense->current_schedule_id,
            'defense_id' => $defense->id,
            'room_id' => $this->room->id,
            'status' => 'current',
        ]);

        $this->assertDatabaseHas('defense_panel_assignments', [
            'defense_id' => $defense->id,
            'user_id' => $this->panelist->id,
        ]);
    }

    public function test_cannot_schedule_overlapping_room_defense(): void
    {
        $action = app(ScheduleDefense::class);

        $startsAt1 = Carbon::now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0);
        $endsAt1 = (clone $startsAt1)->addHours(2);

        $action->handle(
            $this->facilitator,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt1,
            $endsAt1,
            [$this->panelist->id]
        );

        $group2 = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->class->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group 2',
            'leader_student_id' => $this->facilitator->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $startsAt2 = Carbon::now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);
        $endsAt2 = (clone $startsAt2)->addHours(2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Room already has a defense schedule during the requested time interval.');

        $action->handle(
            $this->facilitator,
            $group2,
            'proposal_defense',
            $this->room->id,
            $startsAt2,
            $endsAt2,
            [$this->panelist->id]
        );
    }

    public function test_facilitator_can_reschedule_defense(): void
    {
        $scheduleAction = app(ScheduleDefense::class);
        $rescheduleAction = app(RescheduleDefense::class);

        $startsAt = Carbon::now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $defense = $scheduleAction->handle(
            $this->facilitator,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            [$this->panelist->id]
        );

        $oldScheduleId = $defense->current_schedule_id;

        $newStartsAt = Carbon::now()->addDays(3)->setHour(14)->setMinute(0)->setSecond(0);
        $newEndsAt = (clone $newStartsAt)->addHours(2);

        $updatedDefense = $rescheduleAction->handle(
            $this->facilitator,
            $defense,
            $oldScheduleId,
            $this->room->id,
            $newStartsAt,
            $newEndsAt,
            'Faculty request due to conflict.'
        );

        $this->assertNotEquals($oldScheduleId, $updatedDefense->current_schedule_id);

        $this->assertDatabaseHas('defense_schedules', [
            'id' => $oldScheduleId,
            'status' => 'superseded',
        ]);

        $this->assertDatabaseHas('defense_schedules', [
            'id' => $updatedDefense->id,
            'status' => 'current',
        ]);
    }

    public function test_facilitator_can_cancel_defense(): void
    {
        $scheduleAction = app(ScheduleDefense::class);
        $cancelAction = app(CancelDefense::class);

        $startsAt = Carbon::now()->addDays(2)->setHour(9)->setMinute(0)->setSecond(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $defense = $scheduleAction->handle(
            $this->facilitator,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            [$this->panelist->id]
        );

        $scheduleId = $defense->current_schedule_id;

        $cancelledDefense = $cancelAction->handle(
            $this->facilitator,
            $defense,
            $scheduleId,
            'Student withdrawal.'
        );

        $this->assertEquals('cancelled', $cancelledDefense->status);

        $this->assertDatabaseHas('defense_schedules', [
            'id' => $scheduleId,
            'status' => 'cancelled',
        ]);
    }
}

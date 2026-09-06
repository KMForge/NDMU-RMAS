<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Defense;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\AssignClassDefenseCommittee;
use App\Modules\DefenseScheduling\Actions\BulkScheduleDefenses;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BulkDefenseSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $facilitator;

    private User $adviser;

    private User $chairperson;

    private User $panel1;

    private User $panel2;

    private ResearchClass $researchClass;

    private ResearchClassGroup $classGroup1;

    private ResearchClassGroup $classGroup2;

    private DefenseRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'classes.manage-groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'guard_name' => 'web']);

        $this->facilitator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
        ]);
        $this->facilitator->givePermissionTo('defenses.manage');
        $this->facilitator->givePermissionTo('classes.manage-groups');

        $this->adviser = User::factory()->create([
            'name' => 'Prof Adviser',
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
        ]);
        $this->adviser->givePermissionTo('evaluations.create');

        $this->chairperson = User::factory()->create([
            'name' => 'Prof Chair',
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
        ]);
        $this->chairperson->givePermissionTo('evaluations.create');

        $this->panel1 = User::factory()->create([
            'name' => 'Dr Panelist 1',
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
        ]);
        $this->panel1->givePermissionTo('evaluations.create');

        $this->panel2 = User::factory()->create([
            'name' => 'Engr Panelist 2',
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
        ]);
        $this->panel2->givePermissionTo('evaluations.create');

        $this->researchClass = ResearchClass::query()->forceCreate([
            'facilitator_id' => $this->facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'CS-402 Section B',
            'join_code_hash' => hash('sha256', 'CAP-'.strtoupper(bin2hex(random_bytes(3)))),
            'join_code_encrypted' => 'CAP-123456',
            'is_active' => true,
        ]);

        $this->classGroup1 = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Group Alpha',
            'leader_student_id' => $this->facilitator->id,
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $this->classGroup2 = ResearchClassGroup::query()->forceCreate([
            'research_class_id' => $this->researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Research Group Beta',
            'leader_student_id' => $this->facilitator->id,
            'adviser_id' => $this->adviser->id,
            'created_by' => $this->facilitator->id,
            'status' => 'active',
        ]);

        $this->room = DefenseRoom::create([
            'name' => 'Main AVR Audio-Visual Room',
            'code' => 'AVR-1',
            'capacity' => 50,
            'is_active' => true,
        ]);
    }

    public function test_bulk_scheduling_creates_session_and_schedules_with_shared_window_and_order(): void
    {
        // 1. Assign class-level committee first
        app(AssignClassDefenseCommittee::class)->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );

        $action = app(BulkScheduleDefenses::class);

        $startsAt = '2026-10-15 07:00:00';
        $endsAt = '2026-10-15 18:00:00';

        // Order: Group 2 first (order 1), Group 1 second (order 2)
        $session = $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            roomId: $this->room->id,
            sessionDate: '2026-10-15',
            startsAt: $startsAt,
            endsAt: $endsAt,
            orderedGroupIds: [$this->classGroup2->id, $this->classGroup1->id],
            scheduledByUserId: $this->facilitator->id
        );

        $this->assertDatabaseHas('defense_sessions', [
            'id' => $session->id,
            'research_class_id' => $this->researchClass->id,
            'room_id' => $this->room->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        // Verify Defenses created
        $this->assertDatabaseHas('defenses', [
            'research_class_group_id' => $this->classGroup2->id,
            'defense_type' => 'proposal_defense',
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('defenses', [
            'research_class_group_id' => $this->classGroup1->id,
            'defense_type' => 'proposal_defense',
            'status' => 'scheduled',
        ]);

        // Verify DefenseSchedules have presentation_order and identical shared session window (NOT fragmented timeslots)
        $schedule2 = DefenseSchedule::whereHas('defense', function ($q) {
            $q->where('research_class_group_id', $this->classGroup2->id);
        })->first();

        $schedule1 = DefenseSchedule::whereHas('defense', function ($q) {
            $q->where('research_class_group_id', $this->classGroup1->id);
        })->first();

        $this->assertNotNull($schedule2);
        $this->assertNotNull($schedule1);

        $this->assertEquals(1, $schedule2->presentation_order);
        $this->assertEquals(2, $schedule1->presentation_order);

        $this->assertEquals($session->id, $schedule2->defense_session_id);
        $this->assertEquals($session->id, $schedule1->defense_session_id);

        $this->assertEquals($startsAt, $schedule2->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($endsAt, $schedule2->ends_at->format('Y-m-d H:i:s'));
        $this->assertEquals($startsAt, $schedule1->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals($endsAt, $schedule1->ends_at->format('Y-m-d H:i:s'));

        // Verify defense panel assignments were automatically populated
        $this->assertDatabaseHas('defense_panel_assignments', [
            'defense_id' => $schedule2->defense_id,
            'user_id' => $this->chairperson->id,
            'panel_position' => 'chairperson',
        ]);

        $this->assertDatabaseHas('defense_panel_assignments', [
            'defense_id' => $schedule1->defense_id,
            'user_id' => $this->panel1->id,
            'panel_position' => 'member_1',
        ]);
    }

    public function test_bulk_scheduling_fails_if_groups_lack_assigned_committees(): void
    {
        $this->expectException(ValidationException::class);

        $action = app(BulkScheduleDefenses::class);

        // No committee has been assigned to classGroup1 or classGroup2!
        $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            roomId: $this->room->id,
            sessionDate: '2026-10-15',
            startsAt: '2026-10-15 07:00:00',
            endsAt: '2026-10-15 18:00:00',
            orderedGroupIds: [$this->classGroup1->id],
            scheduledByUserId: $this->facilitator->id
        );
    }

    public function test_bulk_scheduling_detects_room_conflicts(): void
    {
        // Assign committee
        app(AssignClassDefenseCommittee::class)->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            chairpersonId: $this->chairperson->id,
            panelMember1Id: $this->panel1->id,
            panelMember2Id: $this->panel2->id,
            assignedByUserId: $this->facilitator->id
        );

        $action = app(BulkScheduleDefenses::class);

        // Schedule first batch
        $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            roomId: $this->room->id,
            sessionDate: '2026-10-15',
            startsAt: '2026-10-15 07:00:00',
            endsAt: '2026-10-15 12:00:00',
            orderedGroupIds: [$this->classGroup1->id],
            scheduledByUserId: $this->facilitator->id
        );

        // Try to schedule second batch in the same room with overlapping time
        $this->expectException(ValidationException::class);

        $action->execute(
            researchClassId: $this->researchClass->id,
            defenseType: 'proposal_defense',
            roomId: $this->room->id,
            sessionDate: '2026-10-15',
            startsAt: '2026-10-15 10:00:00',
            endsAt: '2026-10-15 15:00:00',
            orderedGroupIds: [$this->classGroup2->id],
            scheduledByUserId: $this->facilitator->id
        );
    }

    public function test_single_schedule_defense_allows_adviser_as_chairperson(): void
    {
        $singleScheduler = app(ScheduleDefense::class);

        // Adviser is selected as Chairperson
        $defense = $singleScheduler->handle(
            actor: $this->facilitator,
            group: $this->classGroup1,
            defenseType: 'proposal_defense',
            roomId: $this->room->id,
            startsAt: Carbon::parse('2026-11-01 09:00:00'),
            endsAt: Carbon::parse('2026-11-01 10:30:00'),
            chairpersonUserId: $this->adviser->id, // Adviser as Chair!
            panelUserIds: [$this->panel1->id, $this->panel2->id]
        );

        $this->assertInstanceOf(Defense::class, $defense);
        $this->assertDatabaseHas('defense_panel_assignments', [
            'defense_id' => $defense->id,
            'user_id' => $this->adviser->id,
            'panel_position' => 'chairperson',
        ]);
    }
}

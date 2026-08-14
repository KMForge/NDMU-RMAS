<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.facilitator.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.student.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.adviser.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboards.panelist.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'forms.res-036.evaluate', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'evaluations.create', 'guard_name' => 'web']);

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
        $this->panelist->givePermissionTo(['dashboards.panelist.view', 'forms.res-036.evaluate', 'evaluations.create']);

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
}

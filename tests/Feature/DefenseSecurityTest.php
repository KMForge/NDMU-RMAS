<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseRoom;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DefenseSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $otherFaculty;

    private User $student;

    private ResearchClass $class;

    private ResearchClassGroup $group;

    private DefenseRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'defenses.manage', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'user_type' => UserType::Admin,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->admin->givePermissionTo(['users.manage', 'defenses.manage']);

        $this->otherFaculty = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $this->otherFaculty->givePermissionTo('defenses.manage');

        $this->student = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $facilitator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
        $facilitator->givePermissionTo('defenses.manage');

        $this->class = ResearchClass::query()->forceCreate([
            'facilitator_id' => $facilitator->id,
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
            'leader_student_id' => $facilitator->id,
            'created_by' => $facilitator->id,
            'status' => 'active',
        ]);

        $this->room = DefenseRoom::create([
            'code' => 'RM-102',
            'name' => 'Conference Room B',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_mutate_defense_schedules(): void
    {
        $action = app(ScheduleDefense::class);

        $startsAt = Carbon::now()->addDays(2)->setHour(9)->setMinute(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $this->expectException(AuthorizationException::class);

        $action->handle(
            $this->admin,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            []
        );
    }

    public function test_non_owner_faculty_cannot_schedule_defense(): void
    {
        $action = app(ScheduleDefense::class);

        $startsAt = Carbon::now()->addDays(2)->setHour(9)->setMinute(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $this->expectException(AuthorizationException::class);

        $action->handle(
            $this->otherFaculty,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            []
        );
    }

    public function test_student_cannot_schedule_defense(): void
    {
        $action = app(ScheduleDefense::class);

        $startsAt = Carbon::now()->addDays(2)->setHour(9)->setMinute(0);
        $endsAt = (clone $startsAt)->addHours(2);

        $this->expectException(AuthorizationException::class);

        $action->handle(
            $this->student,
            $this->group,
            'proposal_defense',
            $this->room->id,
            $startsAt,
            $endsAt,
            []
        );
    }
}

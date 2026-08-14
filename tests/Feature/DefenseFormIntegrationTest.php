<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use App\Modules\OfficialForms\Services\OfficialFormSignatureHasher;
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
}

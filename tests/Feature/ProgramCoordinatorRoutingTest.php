<?php

namespace Tests\Feature;

use App\Livewire\AdminDashboard;
use App\Models\FacultyProfile;
use App\Models\Program;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\Administration\Actions\ManageAcademicConfiguration;
use App\Modules\Classes\Queries\GetFacilitatorClassData;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProgramCoordinatorRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, AcademicStructureSeeder::class]);
    }

    public function test_two_programs_in_one_department_route_to_different_coordinators_and_facilitators(): void
    {
        $it = Program::query()->where('code', 'BSIT')->sole();
        $cs = Program::query()->where('code', 'BSCS')->sole();
        $itCoordinator = $this->coordinator($it);
        $csCoordinator = $this->coordinator($cs);
        $it->update(['coordinator_user_id' => $itCoordinator->id]);
        $cs->update(['coordinator_user_id' => $csCoordinator->id]);
        $itClass = $this->researchClass($it);
        $csClass = $this->researchClass($cs);
        $itCoordinator->update(['updated_at' => now()->addDay()]);

        $resolver = new InstitutionalActorResolver;
        $this->assertSame($itCoordinator->id, $resolver->programCoordinatorForClass($itClass)?->id);
        $this->assertSame($csCoordinator->id, $resolver->programCoordinatorForClass($csClass)?->id);
        $this->assertFalse($resolver->isProgramCoordinator($itCoordinator, $csClass));
        $this->assertFalse($resolver->isProgramCoordinator($csCoordinator, $itClass));
        $this->assertNotSame($itClass->facilitator_id, $csClass->facilitator_id);
        $this->assertSame([$itClass->id], app(GetFacilitatorClassData::class)->for($itCoordinator, activeTab: 'monitoring')['researchClasses']->pluck('id')->all());
        $this->assertSame([$csClass->id], app(GetFacilitatorClassData::class)->for($csCoordinator, activeTab: 'monitoring')['researchClasses']->pluck('id')->all());
    }

    public function test_ambiguous_department_and_inactive_explicit_coordinator_fail_closed(): void
    {
        $program = Program::query()->where('code', 'BSIT')->sole();
        $one = $this->coordinator($program);
        $this->coordinator($program);
        $class = $this->researchClass($program);
        $this->assertNull((new InstitutionalActorResolver)->programCoordinatorForClass($class));
        $program->update(['coordinator_user_id' => $one->id]);
        $one->update(['status' => 'suspended']);
        $resolver = new InstitutionalActorResolver;
        $this->assertNull($resolver->programCoordinatorForClass($class));
        $this->assertTrue($resolver->hasConfiguredProgramCoordinator($class));
    }

    public function test_admin_can_save_coordinators_separately_and_assignment_is_audited(): void
    {
        $it = Program::query()->where('code', 'BSIT')->sole();
        $cs = Program::query()->where('code', 'BSCS')->sole();
        $one = $this->coordinator($it);
        $two = $this->coordinator($cs);
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)->set('tab', 'configuration')
            ->assertSee('Save Coordinator')
            ->call('assignProgramCoordinator', $it->id, $one->id)
            ->assertSet('lastSavedCoordinatorProgramId', $it->id)
            ->assertSee('Coordinator saved successfully.')
            ->assertSee('Saved coordinator:')
            ->assertSee($one->name)
            ->call('assignProgramCoordinator', $cs->id, $two->id)
            ->assertSet('lastSavedCoordinatorProgramId', $cs->id)->assertHasNoErrors();
        $this->assertDatabaseHas('programs', ['id' => $it->id, 'coordinator_user_id' => $one->id]);
        $this->assertDatabaseHas('programs', ['id' => $cs->id, 'coordinator_user_id' => $two->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'academic.program.coordinator-assigned']);
        Livewire::test(AdminDashboard::class)->set('tab', 'configuration')
            ->assertSee($one->name)->assertSee($two->name);
    }

    public function test_invalid_coordinator_error_is_shown_beside_program_and_saved_assignment_is_kept(): void
    {
        $it = Program::query()->where('code', 'BSIT')->sole();
        $one = $this->coordinator($it);
        $it->update(['coordinator_user_id' => $one->id]);
        $other = Program::query()->where('department_id', '!=', $it->department_id)->firstOrFail();
        $wrong = $this->coordinator($other);
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $this->actingAs($admin);
        Livewire::test(AdminDashboard::class)->set('tab', 'configuration')
            ->call('assignProgramCoordinator', $it->id, $wrong->id)
            ->assertHasErrors(['programCoordinator.'.$it->id])
            ->assertSet('lastSavedCoordinatorProgramId', null)
            ->assertSee('Choose an active, verified coordinator assigned to this program’s department.');
        $this->assertSame($one->id, $it->fresh()->coordinator_user_id);
    }

    public function test_assignment_rejects_coordinator_from_another_department(): void
    {
        $it = Program::query()->where('code', 'BSIT')->sole();
        $other = Program::query()->where('department_id', '!=', $it->department_id)->firstOrFail();
        $coordinator = $this->coordinator($other);
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $this->expectException(ValidationException::class);
        app(ManageAcademicConfiguration::class)->assignProgramCoordinator($admin, $it, $coordinator->id);
    }

    public function test_legacy_mixed_class_resolves_each_groups_program_instead_of_first_group(): void
    {
        $it = Program::query()->where('code', 'BSIT')->sole();
        $cs = Program::query()->where('code', 'BSCS')->sole();
        $one = $this->coordinator($it);
        $two = $this->coordinator($cs);
        $it->update(['coordinator_user_id' => $one->id]);
        $cs->update(['coordinator_user_id' => $two->id]);
        $class = $this->researchClass($it);
        $class->update(['program_id' => null]);
        $itGroup = $this->group($class, $it);
        $csGroup = $this->group($class, $cs);
        $resolver = new InstitutionalActorResolver;
        $this->assertNull($resolver->programCoordinatorForClass($class->fresh()));
        $this->assertSame($one->id, $resolver->programCoordinatorForGroup($itGroup)?->id);
        $this->assertSame($two->id, $resolver->programCoordinatorForGroup($csGroup)?->id);
        $this->assertFalse($resolver->isProgramCoordinator($one, $class, $csGroup));
    }

    public function test_only_administrator_with_settings_permission_can_change_coordinator(): void
    {
        $program = Program::query()->where('code', 'BSIT')->sole();
        $coordinator = $this->coordinator($program);
        $this->expectException(HttpException::class);
        app(ManageAcademicConfiguration::class)->assignProgramCoordinator($coordinator, $program, $coordinator->id);
    }

    public function test_facilitator_can_select_program_when_creating_class_and_invalid_program_is_rejected(): void
    {
        $facilitator = User::factory()->create(['user_type' => 'faculty']);
        $facilitator->assignRole('research-facilitator');
        $program = Program::query()->where('code', 'BSCS')->sole();
        $payload = ['creation_token' => (string) Str::uuid(), 'name' => 'BSCS Research Class', 'max_students' => 50, 'program_id' => $program->id];
        $this->actingAs($facilitator)->postJson(route('facilitator.classes.store'), $payload)->assertCreated();
        $this->assertDatabaseHas('research_classes', ['name' => 'BSCS Research Class', 'program_id' => $program->id]);
        $this->actingAs($facilitator)->postJson(route('facilitator.classes.store'), [...$payload, 'creation_token' => (string) Str::uuid(), 'program_id' => 999999])->assertUnprocessable()->assertJsonValidationErrors('program_id');
    }

    public function test_program_assignment_migration_is_reversible(): void
    {
        $migration = require database_path('migrations/2026_10_11_000000_add_program_coordinator_and_class_program.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('programs', 'coordinator_user_id'));
        $this->assertFalse(Schema::hasColumn('research_classes', 'program_id'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('programs', 'coordinator_user_id'));
        $this->assertTrue(Schema::hasColumn('research_classes', 'program_id'));
    }

    private function coordinator(Program $program): User
    {
        $user = User::factory()->create(['user_type' => 'faculty']);
        $user->assignRole('program-coordinator');
        FacultyProfile::query()->create(['user_id' => $user->id, 'department_id' => $program->department_id, 'employee_number' => 'COORD-'.$user->id]);

        return $user;
    }

    private function researchClass(Program $program): ResearchClass
    {
        $facilitator = User::factory()->create(['user_type' => 'faculty']);
        $facilitator->assignRole('research-facilitator');
        $class = new ResearchClass(['facilitator_id' => $facilitator->id, 'program_id' => $program->id, 'creation_token' => (string) Str::uuid(), 'name' => $program->code.' Class', 'max_students' => 50, 'is_active' => true]);
        $class->setJoinCode(Str::random(8));
        $class->save();

        return $class;
    }

    private function group(ResearchClass $class, Program $program): ResearchClassGroup
    {
        $student = User::factory()->create();
        StudentProfile::query()->create(['user_id' => $student->id, 'program_id' => $program->id, 'student_number' => 'STUDENT-'.$student->id]);
        $group = ResearchClassGroup::query()->create(['research_class_id' => $class->id, 'leader_student_id' => $student->id, 'created_by' => $class->facilitator_id, 'creation_token' => (string) Str::uuid(), 'name' => $program->code.' Group', 'status' => 'active']);
        $enrollment = ResearchClassEnrollment::query()->create(['research_class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'requested_at' => now(), 'joined_at' => now()]);
        ResearchClassGroupMember::query()->create(['research_class_group_id' => $group->id, 'research_class_id' => $class->id, 'research_class_enrollment_id' => $enrollment->id, 'student_id' => $student->id, 'assigned_by' => $class->facilitator_id]);

        return $group;
    }
}

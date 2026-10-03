<?php

namespace Tests\Feature\UserManagement;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\User;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyDepartmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, AcademicStructureSeeder::class]);
    }

    public function test_program_coordinator_can_be_resolved_through_secondary_department_assignment(): void
    {
        $csd = Department::query()->where('code', 'CSD')->firstOrFail();
        $eece = Department::query()->where('code', 'EECE')->firstOrFail();
        $coordinator = User::factory()->create([
            'user_type' => UserType::Faculty,
            'status' => AccountStatus::Active,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'department' => $eece->name,
        ]);
        $coordinator->assignRole('program-coordinator');
        $profile = FacultyProfile::query()->create([
            'user_id' => $coordinator->id,
            'department_id' => $eece->id,
            'employee_number' => 'EMP-COORD-001',
        ]);
        $profile->departments()->sync([$eece->id, $csd->id]);

        $resolved = app(InstitutionalActorResolver::class)->programCoordinatorForDepartmentId($csd->id);

        $this->assertTrue($coordinator->is($resolved));
    }
}

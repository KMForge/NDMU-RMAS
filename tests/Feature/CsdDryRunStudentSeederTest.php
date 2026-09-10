<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CsdDryRunStudentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CsdDryRunStudentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_active_verified_csd_students_idempotently(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
            CsdDryRunStudentSeeder::class,
            CsdDryRunStudentSeeder::class,
        ]);

        $students = User::query()
            ->whereIn('email', [
                'csd.dryrun1@ndmu.edu.ph',
                'csd.dryrun2@ndmu.edu.ph',
                'csd.dryrun3@ndmu.edu.ph',
                'csd.dryrun4@ndmu.edu.ph',
                'csd.dryrun5@ndmu.edu.ph',
                'csd.dryrun6@ndmu.edu.ph',
            ])
            ->with('studentProfile.program.department')
            ->get();

        $this->assertCount(6, $students);

        foreach ($students as $student) {
            $this->assertSame(UserType::Student, $student->user_type);
            $this->assertSame(AccountStatus::Active, $student->status);
            $this->assertNotNull($student->approved_at);
            $this->assertNotNull($student->email_verified_at);
            $this->assertSame('Computer Studies Department', $student->department);
            $this->assertSame('CSD', $student->studentProfile?->program?->department?->code);
            $this->assertSame('BSIT', $student->studentProfile?->program?->code);
            $this->assertTrue($student->hasExactRoles(['student']));
            $this->assertTrue(Hash::check('Password!12345', $student->password));
        }
    }
}

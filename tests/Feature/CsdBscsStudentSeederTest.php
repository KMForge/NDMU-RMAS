<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CsdBscsStudentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CsdBscsStudentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_six_realistic_bscs_students_idempotently(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
            CsdBscsStudentSeeder::class,
            CsdBscsStudentSeeder::class,
        ]);

        $students = User::query()
            ->whereIn('email', array_column(CsdBscsStudentSeeder::STUDENTS, 'email'))
            ->with('studentProfile.program.department')
            ->get()
            ->keyBy('email');

        $this->assertCount(6, $students);

        foreach (CsdBscsStudentSeeder::STUDENTS as $account) {
            $student = $students->get($account['email']);

            $this->assertNotNull($student);
            $this->assertSame($account['student_id'], $student->student_id);
            $this->assertSame(UserType::Student, $student->user_type);
            $this->assertSame(AccountStatus::Active, $student->status);
            $this->assertNotNull($student->approved_at);
            $this->assertNotNull($student->email_verified_at);
            $this->assertSame('CSD', $student->studentProfile?->program?->department?->code);
            $this->assertSame('BSCS', $student->studentProfile?->program?->code);
            $this->assertSame($account['year_level'], $student->studentProfile?->year_level);
            $this->assertTrue($student->hasExactRoles(['student']));
            $this->assertTrue(Hash::check(CsdBscsStudentSeeder::PASSWORD, $student->password));
        }
    }
}

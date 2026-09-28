<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\CsdEvaluationUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CsdEvaluationUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_idempotent_csd_faculty_and_bsit_student_evaluation_accounts(): void
    {
        $this->seed(CsdEvaluationUserSeeder::class);
        $this->seed(CsdEvaluationUserSeeder::class);

        $faculty = User::query()
            ->whereIn('email', collect(range(1, 10))->map(fn (int $number) => "faculty{$number}@ndmu.edu.ph"))
            ->with(['facultyProfile.department', 'roles'])
            ->get();
        $students = User::query()
            ->whereIn('email', collect(range(1, 10))->map(fn (int $number) => "student{$number}@ndmu.edu.ph"))
            ->with(['studentProfile.program.department', 'roles'])
            ->get();

        $this->assertCount(10, $faculty);
        $this->assertCount(10, $students);

        foreach ($faculty as $user) {
            $this->assertSame(UserType::Faculty, $user->user_type);
            $this->assertSame(AccountStatus::Active, $user->status);
            $this->assertNotNull($user->approved_at);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check(CsdEvaluationUserSeeder::PASSWORD, $user->password));
            $this->assertSame('CSD', $user->facultyProfile?->department?->code);
            $this->assertTrue($user->hasAllRoles(['thesis-adviser', 'panel-member']));
        }

        foreach ($students as $user) {
            $this->assertSame(UserType::Student, $user->user_type);
            $this->assertSame(AccountStatus::Active, $user->status);
            $this->assertNotNull($user->approved_at);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check(CsdEvaluationUserSeeder::PASSWORD, $user->password));
            $this->assertSame('BSIT', $user->studentProfile?->program?->code);
            $this->assertSame('CSD', $user->studentProfile?->program?->department?->code);
            $this->assertTrue($user->hasExactRoles('student'));
        }
    }
}

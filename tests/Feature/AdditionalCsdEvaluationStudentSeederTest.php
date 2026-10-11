<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\AdditionalCsdEvaluationStudentSeeder;
use Database\Seeders\CsdEvaluationUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdditionalCsdEvaluationStudentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_only_students_11_to_20_with_matching_bsit_csd_profiles(): void
    {
        $this->seed(CsdEvaluationUserSeeder::class);
        $before = User::query()->orderBy('id')->get()->map(fn ($user) => $user->getRawOriginal())->all();
        $this->seed(AdditionalCsdEvaluationStudentSeeder::class);
        $this->assertSame($before, User::query()->where('id', '<=', 20)->orderBy('id')->get()->map(fn ($user) => $user->getRawOriginal())->all());
        $students = User::query()->whereIn('email', collect(range(11, 20))->map(fn ($number) => "student{$number}@ndmu.edu.ph"))->with('studentProfile.program.department')->get();
        $this->assertCount(10, $students);
        foreach ($students as $student) {
            $this->assertSame(UserType::Student, $student->user_type);
            $this->assertSame(AccountStatus::Active, $student->status);
            $this->assertNotNull($student->approved_at);
            $this->assertNotNull($student->email_verified_at);
            $this->assertSame('BSIT', $student->studentProfile->program->code);
            $this->assertSame('CSD', $student->studentProfile->program->department->code);
            $this->assertSame(4, $student->studentProfile->year_level);
            $this->assertSame('4th', $student->year_level);
            $this->assertTrue($student->hasExactRoles('student'));
            $this->assertTrue(Hash::check(CsdEvaluationUserSeeder::PASSWORD, $student->password));
        }
        $student = User::query()->where('email', 'student11@ndmu.edu.ph')->firstOrFail();
        $student->update(['name' => 'Edited Student', 'password' => Hash::make('Changed!Password123')]);
        $after = User::query()->orderBy('id')->get()->map(fn ($user) => $user->getRawOriginal())->all();
        $this->seed(AdditionalCsdEvaluationStudentSeeder::class);
        $this->assertSame($after, User::query()->orderBy('id')->get()->map(fn ($user) => $user->getRawOriginal())->all());
        $this->assertDatabaseCount('research_classes', 0);
        $this->assertDatabaseCount('research_class_groups', 0);
    }
}

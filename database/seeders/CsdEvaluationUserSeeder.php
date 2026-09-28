<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\Program;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class CsdEvaluationUserSeeder extends Seeder
{
    public const PASSWORD = 'Password!12345';

    public const FACULTY_COUNT = 10;

    public const STUDENT_COUNT = 10;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        $department = Department::query()->where('code', 'CSD')->first();
        $program = Program::query()
            ->where('code', 'BSIT')
            ->whereHas('department', fn ($query) => $query->where('code', 'CSD'))
            ->first();

        if ($department === null || $program === null) {
            throw new LogicException('The CSD department and BSIT program must exist before evaluation accounts can be seeded.');
        }

        $programLabel = collect(config('academic.programs'))
            ->firstWhere('code', 'BSIT')['label'] ?? $program->name;

        DB::transaction(function () use ($department, $program, $programLabel): void {
            $this->seedFaculty($department);
            $this->seedStudents($department, $program, $programLabel);
        });

        $this->command?->newLine();
        $this->command?->info('20 CSD evaluation accounts are ready.');
        $this->command?->line('Faculty: faculty1@ndmu.edu.ph through faculty10@ndmu.edu.ph');
        $this->command?->line('Students: student1@ndmu.edu.ph through student10@ndmu.edu.ph');
        $this->command?->line('Password for all accounts: '.self::PASSWORD);
    }

    private function seedFaculty(Department $department): void
    {
        foreach (range(1, self::FACULTY_COUNT) as $number) {
            $employeeNumber = sprintf('EMP-CSD-EVAL-%03d', $number);
            $user = User::query()->updateOrCreate(
                ['email' => "faculty{$number}@ndmu.edu.ph"],
                [
                    'name' => "CSD Faculty {$number}",
                    'first_name' => 'Faculty',
                    'last_name' => (string) $number,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => AccountStatus::Active,
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                    'department' => $department->name,
                    'user_type' => UserType::Faculty,
                    'student_id' => null,
                    'program' => null,
                    'year_level' => null,
                ],
            );

            StudentProfile::query()->where('user_id', $user->getKey())->delete();
            $user->syncRoles(['thesis-adviser', 'panel-member']);

            FacultyProfile::query()
                ->where('employee_number', $employeeNumber)
                ->where('user_id', '!=', $user->getKey())
                ->update(['employee_number' => $employeeNumber.'-REPLACED']);

            FacultyProfile::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'department_id' => $department->getKey(),
                    'employee_number' => $employeeNumber,
                    'academic_rank' => 'Evaluation Faculty / Adviser / Panel Member',
                    'specialization' => 'Computer Studies',
                ],
            );
        }
    }

    private function seedStudents(Department $department, Program $program, string $programLabel): void
    {
        foreach (range(1, self::STUDENT_COUNT) as $number) {
            $studentNumber = sprintf('STU-CSD-EVAL-%03d', $number);
            $user = User::query()->updateOrCreate(
                ['email' => "student{$number}@ndmu.edu.ph"],
                [
                    'name' => "BSIT Student {$number}",
                    'first_name' => 'Student',
                    'last_name' => (string) $number,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => AccountStatus::Active,
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                    'department' => $department->name,
                    'user_type' => UserType::Student,
                    'student_id' => $studentNumber,
                    'program' => $programLabel,
                    'year_level' => '4th',
                ],
            );

            FacultyProfile::query()->where('user_id', $user->getKey())->delete();
            $user->syncRoles('student');

            StudentProfile::query()
                ->where('student_number', $studentNumber)
                ->where('user_id', '!=', $user->getKey())
                ->update(['student_number' => $studentNumber.'-REPLACED']);

            StudentProfile::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'program_id' => $program->getKey(),
                    'student_number' => $studentNumber,
                    'year_level' => 4,
                ],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Program;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Spatie\Permission\Models\Role;

class CsdDryRunStudentSeeder extends Seeder
{
    private const PASSWORD = 'Password!12345';

    /**
     * @var list<array{name: string, email: string, student_id: string}>
     */
    private const STUDENTS = [
        [
            'name' => 'CSD Dry Run Student 1',
            'email' => 'csd.dryrun1@ndmu.edu.ph',
            'student_id' => 'STU-CSD-2026-001',
        ],
        [
            'name' => 'CSD Dry Run Student 2',
            'email' => 'csd.dryrun2@ndmu.edu.ph',
            'student_id' => 'STU-CSD-2026-002',
        ],
        [
            'name' => 'CSD Dry Run Student 3',
            'email' => 'csd.dryrun3@ndmu.edu.ph',
            'student_id' => 'STU-CSD-2026-003',
        ],
        [
            'name' => 'CSD Dry Run Student 4',
            'email' => 'csd.dryrun4@ndmu.edu.ph',
            'student_id' => 'STU-CSD-2026-004',
        ],
        [
            'name' => 'CSD Dry Run Student 5',
            'email' => 'csd.dryrun5@ndmu.edu.ph',
            'student_id' => 'STU-CSD-2026-005',
        ],
        [
            'name' => 'CSD Dry Run Student 6',
            'email' => 'csd.dryrun6@ndmu.edu.ph',
            'student_id' => 'STU-CSD-2026-006',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('CSD dry-run student accounts were skipped outside the local/testing environment.');

            return;
        }

        $program = Program::query()
            ->where('code', 'BSIT')
            ->whereHas('department', fn ($query) => $query->where('code', 'CSD'))
            ->first();
        $studentRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'student')
            ->first();

        if ($program === null || $studentRole === null) {
            throw new LogicException('Seed the academic structure and roles before seeding CSD dry-run students.');
        }

        $departmentName = collect(config('academic.departments'))
            ->firstWhere('code', 'CSD')['name'] ?? 'Computer Studies Department';
        $programLabel = collect(config('academic.programs'))
            ->firstWhere('code', 'BSIT')['label'] ?? $program->name;

        foreach (self::STUDENTS as $account) {
            $student = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'student_id' => $account['student_id'],
                    'program' => $programLabel,
                    'year_level' => '4th',
                    'department' => $departmentName,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => AccountStatus::Active,
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                    'user_type' => UserType::Student,
                ],
            );

            $student->syncRoles($studentRole);

            StudentProfile::query()->updateOrCreate(
                ['user_id' => $student->getKey()],
                [
                    'program_id' => $program->getKey(),
                    'student_number' => $account['student_id'],
                    'year_level' => 4,
                ],
            );
        }

        $this->command?->info(sprintf('%d active CSD dry-run student accounts were seeded.', count(self::STUDENTS)));
    }
}

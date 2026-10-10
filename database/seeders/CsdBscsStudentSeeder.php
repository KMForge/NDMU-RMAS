<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Program;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Spatie\Permission\Models\Role;

class CsdBscsStudentSeeder extends Seeder
{
    public const PASSWORD = 'Password!12345';

    /**
     * Institutional emails use the student's given-name and middle-name initials
     * followed by their surname, matching the NDMU email convention.
     *
     * @var list<array{
     *     first_name: string,
     *     middle_name: string,
     *     last_name: string,
     *     email: string,
     *     student_id: string,
     *     year_level: int
     * }>
     */
    public const STUDENTS = [
        [
            'first_name' => 'Althea',
            'middle_name' => 'Marie Reyes',
            'last_name' => 'Villanueva',
            'email' => 'amrvillanueva@ndmu.edu.ph',
            'student_id' => '2023671',
            'year_level' => 4,
        ],
        [
            'first_name' => 'John',
            'middle_name' => 'Carlo Santos',
            'last_name' => 'Mendoza',
            'email' => 'jcsmendoza@ndmu.edu.ph',
            'student_id' => '2023824',
            'year_level' => 4,
        ],
        [
            'first_name' => 'Nicole',
            'middle_name' => 'Anne Flores',
            'last_name' => 'Bautista',
            'email' => 'nafbautista@ndmu.edu.ph',
            'student_id' => '2024146',
            'year_level' => 3,
        ],
        [
            'first_name' => 'Mark',
            'middle_name' => 'Daniel Cruz',
            'last_name' => 'Navarro',
            'email' => 'mdcnavarro@ndmu.edu.ph',
            'student_id' => '2024389',
            'year_level' => 3,
        ],
        [
            'first_name' => 'Sophia',
            'middle_name' => 'Mae Garcia',
            'last_name' => 'Domingo',
            'email' => 'smgdomingo@ndmu.edu.ph',
            'student_id' => '2025072',
            'year_level' => 2,
        ],
        [
            'first_name' => 'Joshua',
            'middle_name' => 'Paul Aquino',
            'last_name' => 'Ramos',
            'email' => 'jparamos@ndmu.edu.ph',
            'student_id' => '2025264',
            'year_level' => 2,
        ],
    ];

    public function run(): void
    {
        $program = Program::query()
            ->with('department')
            ->where('code', 'BSCS')
            ->whereHas('department', fn ($query) => $query->where('code', 'CSD'))
            ->first();
        $studentRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'student')
            ->first();

        if ($program === null || $studentRole === null) {
            throw new LogicException('Seed the academic structure and roles before seeding CSD BSCS students.');
        }

        $programLabel = collect(config('academic.programs'))
            ->firstWhere('code', 'BSCS')['label'] ?? $program->name;
        $departmentName = $program->department?->name ?? 'Computer Studies Department';

        DB::transaction(function () use ($program, $studentRole, $programLabel, $departmentName): void {
            foreach (self::STUDENTS as $account) {
                $conflictingUser = User::query()
                    ->where('student_id', $account['student_id'])
                    ->where('email', '!=', $account['email'])
                    ->first();

                if ($conflictingUser !== null) {
                    throw new LogicException(
                        "Student ID {$account['student_id']} already belongs to another account."
                    );
                }

                $name = implode(' ', [
                    $account['first_name'],
                    $account['middle_name'],
                    $account['last_name'],
                ]);

                $student = User::query()->updateOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $name,
                        'first_name' => $account['first_name'],
                        'middle_name' => $account['middle_name'],
                        'last_name' => $account['last_name'],
                        'student_id' => $account['student_id'],
                        'program' => $programLabel,
                        'year_level' => $this->ordinal($account['year_level']),
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
                        'year_level' => $account['year_level'],
                    ],
                );
            }
        });

        $this->command?->info('Six active and verified CSD BSCS student accounts were seeded.');
        $this->command?->line('Password for all accounts: '.self::PASSWORD);
    }

    private function ordinal(int $yearLevel): string
    {
        return $yearLevel.match ($yearLevel) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }
}

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
use Illuminate\Support\Facades\Hash;

class AllUsersSeeder extends Seeder
{
    /**
     * Local/testing login credentials. Keep one account per line for easy reference.
     */
    private const LOGIN_ACCOUNTS = [
        ['role' => 'System administrator', 'email' => 'admin@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'College dean', 'email' => 'l.castillo@ndmu.edu.ph', 'password' => 'Password!12345'],
        // CSD
        ['role' => 'CSD Facilitator / Adviser', 'email' => 'r.dela-paz@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CSD Coordinator / Facilitator / Instructor', 'email' => 'j.montero@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CSD Adviser / Panelist', 'email' => 'l.fernandez@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CSD Adviser / Panelist', 'email' => 'a.turing@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CSD Adviser / Language Editor', 'email' => 'g.hopper@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CSD Adviser / Technical Editor', 'email' => 'd.ritchie@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CSD Adviser / Validator', 'email' => 'a.lovelace@ndmu.edu.ph', 'password' => 'Password!12345'],
        // EECE
        ['role' => 'EECE Coordinator / Adviser', 'email' => 'a.santos@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'EECE Facilitator / Adviser', 'email' => 'm.diaz@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'EECE Adviser / Panelist', 'email' => 'n.tesla@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'EECE Adviser / Panelist', 'email' => 'j.maxwell@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'EECE Adviser / Panelist', 'email' => 'c.shannon@ndmu.edu.ph', 'password' => 'Password!12345'],
        // CED
        ['role' => 'CED Coordinator / Adviser', 'email' => 'r.garcia@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CED Facilitator / Adviser', 'email' => 'b.ramos@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CED Adviser / Panelist', 'email' => 's.reyes@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CED Adviser / Panelist', 'email' => 'i.brunel@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'CED Adviser / Panelist', 'email' => 'e.warren@ndmu.edu.ph', 'password' => 'Password!12345'],
        // AD
        ['role' => 'AD Coordinator / Adviser', 'email' => 'm.tan@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'AD Facilitator / Adviser', 'email' => 'p.cruz@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'AD Adviser / Panelist', 'email' => 'j.tan@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'AD Adviser / Panelist', 'email' => 'f.wright@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'AD Adviser / Panelist', 'email' => 'z.hadid@ndmu.edu.ph', 'password' => 'Password!12345'],
        // Students
        ['role' => 'Student (active 1 - leader)', 'email' => 'student.active1@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active 2)', 'email' => 'student.active2@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active 3)', 'email' => 'student.active3@ndmu.edu.ph', 'password' => 'Password!12345'],
    ];

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(AcademicStructureSeeder::class);
        $this->seedBootstrapAdministrator();

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('Demo and test accounts were skipped outside the local/testing environment.');

            return;
        }

        $this->seedStaffAccounts();
        $this->seedPendingStudentAccounts();
        $this->seedActiveStudentAccounts();
        $this->seedTestStudentAccount();

        $this->command?->info('All local user accounts were seeded successfully with departmental affiliations.');
        $this->command?->info('Login credentials are listed at the top of AllUsersSeeder.php.');
    }

    private function seedBootstrapAdministrator(): void
    {
        $email = config('ndmu-rmas.bootstrap_admin.email');
        $password = config('ndmu-rmas.bootstrap_admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || strlen($password) < 12) {
            $this->command?->warn('Bootstrap administrator skipped; configure a strong ADMIN_EMAIL and ADMIN_PASSWORD.');

            return;
        }

        $administrator = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('ndmu-rmas.bootstrap_admin.name', 'System Administrator'),
                'password' => Hash::make($password),
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'user_type' => UserType::Admin,
            ],
        );

        $administrator->syncRoles('administrator');
    }

    private function seedStaffAccounts(): void
    {
        $csdId = Department::query()->where('code', 'CSD')->value('id') ?? 1;
        $eeceId = Department::query()->where('code', 'EECE')->value('id') ?? 2;
        $cedId = Department::query()->where('code', 'CED')->value('id') ?? 3;
        $adId = Department::query()->where('code', 'AD')->value('id') ?? 4;

        $accounts = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@ndmu.edu.ph',
                'roles' => ['administrator'],
                'department_name' => 'College of Engineering, Architecture, and Computing',
                'department_id' => $csdId,
                'emp_no' => 'EMP-ADMIN-001',
                'rank' => 'Administrator',
            ],
            [
                'name' => 'Dr. Lourdes Castillo',
                'email' => 'l.castillo@ndmu.edu.ph',
                'roles' => ['dean'],
                'department_name' => 'College of Engineering, Architecture, and Computing',
                'department_id' => $csdId,
                'emp_no' => 'EMP-DEAN-001',
                'rank' => 'Dean / Full Professor',
            ],

            // 💻 Computer Studies Department (CSD)
            [
                'name' => 'Dr. Rosario Dela Paz',
                'email' => 'r.dela-paz@ndmu.edu.ph',
                'roles' => ['research-facilitator', 'research-instructor', 'thesis-adviser', 'panel-member'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-001',
                'rank' => 'Associate Professor / Research Facilitator',
            ],
            [
                'name' => 'Engr. Jose Montero',
                'email' => 'j.montero@ndmu.edu.ph',
                'roles' => ['program-coordinator', 'research-facilitator', 'thesis-adviser', 'research-instructor', 'panel-member'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-002',
                'rank' => 'Associate Professor / Program Coordinator',
            ],
            [
                'name' => 'Prof. Lucia Fernandez',
                'email' => 'l.fernandez@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-003',
                'rank' => 'Assistant Professor / Panel Member',
            ],
            [
                'name' => 'Prof. Alan Turing',
                'email' => 'a.turing@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-004',
                'rank' => 'Associate Professor / Panel Member',
            ],
            [
                'name' => 'Prof. Grace Hopper',
                'email' => 'g.hopper@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member', 'language-editor'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-005',
                'rank' => 'Associate Professor / Language Editor',
            ],
            [
                'name' => 'Prof. Dennis Ritchie',
                'email' => 'd.ritchie@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member', 'technical-editor'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-006',
                'rank' => 'Assistant Professor / Technical Editor',
            ],
            [
                'name' => 'Prof. Ada Lovelace',
                'email' => 'a.lovelace@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member', 'instrument-validator'],
                'department_name' => 'Computer Studies Department',
                'department_id' => $csdId,
                'emp_no' => 'EMP-CSD-007',
                'rank' => 'Assistant Professor / Instrument Validator',
            ],

            // ⚡ Electrical, Electronics, and Computer Engineering (EECE)
            [
                'name' => 'Dr. Antonio Santos',
                'email' => 'a.santos@ndmu.edu.ph',
                'roles' => ['panel-member', 'thesis-adviser', 'program-coordinator'],
                'department_name' => 'Electrical, Electronics, and Computer Engineering Department',
                'department_id' => $eeceId,
                'emp_no' => 'EMP-EECE-001',
                'rank' => 'Associate Professor / Program Coordinator',
            ],
            [
                'name' => 'Engr. Michael Diaz',
                'email' => 'm.diaz@ndmu.edu.ph',
                'roles' => ['research-facilitator', 'thesis-adviser', 'panel-member'],
                'department_name' => 'Electrical, Electronics, and Computer Engineering Department',
                'department_id' => $eeceId,
                'emp_no' => 'EMP-EECE-002',
                'rank' => 'Assistant Professor / Research Facilitator',
            ],
            [
                'name' => 'Engr. Nikola Tesla',
                'email' => 'n.tesla@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Electrical, Electronics, and Computer Engineering Department',
                'department_id' => $eeceId,
                'emp_no' => 'EMP-EECE-003',
                'rank' => 'Professor / Panel Member',
            ],
            [
                'name' => 'Engr. James Maxwell',
                'email' => 'j.maxwell@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Electrical, Electronics, and Computer Engineering Department',
                'department_id' => $eeceId,
                'emp_no' => 'EMP-EECE-004',
                'rank' => 'Associate Professor / Panel Member',
            ],
            [
                'name' => 'Engr. Claude Shannon',
                'email' => 'c.shannon@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Electrical, Electronics, and Computer Engineering Department',
                'department_id' => $eeceId,
                'emp_no' => 'EMP-EECE-005',
                'rank' => 'Assistant Professor / Panel Member',
            ],

            // 🏗️ Civil Engineering Department (CED)
            [
                'name' => 'Dr. Reyna Garcia',
                'email' => 'r.garcia@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'program-coordinator', 'panel-member'],
                'department_name' => 'Civil Engineering Department',
                'department_id' => $cedId,
                'emp_no' => 'EMP-CED-001',
                'rank' => 'Professor / Program Coordinator',
            ],
            [
                'name' => 'Dr. Benjamin Ramos',
                'email' => 'b.ramos@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member', 'research-facilitator'],
                'department_name' => 'Civil Engineering Department',
                'department_id' => $cedId,
                'emp_no' => 'EMP-CED-002',
                'rank' => 'Associate Professor',
            ],
            [
                'name' => 'Engr. Sarah Reyes',
                'email' => 's.reyes@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Civil Engineering Department',
                'department_id' => $cedId,
                'emp_no' => 'EMP-CED-003',
                'rank' => 'Assistant Professor / Panel Member',
            ],
            [
                'name' => 'Engr. Isambard Brunel',
                'email' => 'i.brunel@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Civil Engineering Department',
                'department_id' => $cedId,
                'emp_no' => 'EMP-CED-004',
                'rank' => 'Professor / Panel Member',
            ],
            [
                'name' => 'Engr. Emily Warren',
                'email' => 'e.warren@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Civil Engineering Department',
                'department_id' => $cedId,
                'emp_no' => 'EMP-CED-005',
                'rank' => 'Assistant Professor / Panel Member',
            ],

            // 🏛️ Architecture Department (AD)
            [
                'name' => 'Dr. Michael Tan',
                'email' => 'm.tan@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'program-coordinator', 'panel-member'],
                'department_name' => 'Architecture Department',
                'department_id' => $adId,
                'emp_no' => 'EMP-AD-001',
                'rank' => 'Professor / Program Coordinator',
            ],
            [
                'name' => 'Prof. Patricia Cruz',
                'email' => 'p.cruz@ndmu.edu.ph',
                'roles' => ['panel-member', 'thesis-adviser', 'research-facilitator'],
                'department_name' => 'Architecture Department',
                'department_id' => $adId,
                'emp_no' => 'EMP-AD-002',
                'rank' => 'Assistant Professor',
            ],
            [
                'name' => 'Ar. Jonathan Tan',
                'email' => 'j.tan@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Architecture Department',
                'department_id' => $adId,
                'emp_no' => 'EMP-AD-003',
                'rank' => 'Associate Professor / Panel Member',
            ],
            [
                'name' => 'Ar. Frank Wright',
                'email' => 'f.wright@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Architecture Department',
                'department_id' => $adId,
                'emp_no' => 'EMP-AD-004',
                'rank' => 'Professor / Panel Member',
            ],
            [
                'name' => 'Ar. Zaha Hadid',
                'email' => 'z.hadid@ndmu.edu.ph',
                'roles' => ['thesis-adviser', 'panel-member'],
                'department_name' => 'Architecture Department',
                'department_id' => $adId,
                'emp_no' => 'EMP-AD-005',
                'rank' => 'Professor / Panel Member',
            ],
        ];

        foreach ($accounts as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($this->passwordFor($data['email'])),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subMonths(6),
                    'email_verified_at' => now()->subMonths(6),
                    'department' => $data['department_name'],
                    'user_type' => in_array('administrator', $data['roles'], true) ? UserType::Admin : UserType::Faculty,
                ],
            );

            $user->syncRoles($data['roles']);

            if (! in_array('administrator', $data['roles'], true)) {
                FacultyProfile::query()
                    ->where('employee_number', $data['emp_no'])
                    ->where('user_id', '!=', $user->id)
                    ->update(['employee_number' => $data['emp_no'].'-OLD-'.$user->id]);

                FacultyProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department_id' => $data['department_id'],
                        'employee_number' => $data['emp_no'],
                        'academic_rank' => $data['rank'],
                        'specialization' => $data['department_name'],
                    ],
                );
            }
        }
    }

    private function seedPendingStudentAccounts(): void
    {
        $accounts = [
            ['Juan Dela Cruz', 'juan.delacruz@ndmu.edu.ph', 'STU-2026-0051', 'BSCE', '3rd'],
            ['Ana Reyes', 'ana.reyes@ndmu.edu.ph', 'STU-2026-0052', 'BSIT', '4th'],
            ['Kevin Aguila', 'kevin.aguila@ndmu.edu.ph', 'STU-2026-0053', 'BSARCH', '2nd'],
            ['Clara Nieto', 'clara.nieto@ndmu.edu.ph', 'STU-2026-0054', 'BSCS', '3rd'],
            ['Dante Flores', 'dante.flores@ndmu.edu.ph', 'STU-2026-0055', 'BSECE', '4th'],
        ];

        foreach ($accounts as [$name, $email, $studentId, $progCode, $yearLevel]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'student_id' => $studentId,
                    'program' => $this->programLabel($progCode),
                    'year_level' => $yearLevel,
                    'password' => Hash::make($this->passwordFor($email)),
                    'status' => AccountStatus::Pending,
                    'approved_at' => null,
                    'email_verified_at' => now()->subDays(5),
                    'user_type' => UserType::Student,
                ],
            );

            $user->syncRoles('student');

            $prog = Program::query()->where('code', $progCode)->first();
            if ($prog) {
                StudentProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'program_id' => $prog->id,
                        'student_number' => $studentId,
                        'year_level' => (int) filter_var($yearLevel, FILTER_SANITIZE_NUMBER_INT) ?: 3,
                    ],
                );
            }
        }
    }

    private function seedActiveStudentAccounts(): void
    {
        $programsByNumber = [
            1 => 'BSCS',
            2 => 'BSCS',
            3 => 'BSCS',
            4 => 'BSIT',
            5 => 'BSIT',
            6 => 'BSEE',
            7 => 'BSCE',
            8 => 'BSCE',
            9 => 'BSARCH',
            10 => 'BSARCH',
            11 => 'BLIS',
        ];

        for ($number = 1; $number <= 11; $number++) {
            $email = "student.active{$number}@ndmu.edu.ph";
            $progCode = $programsByNumber[$number] ?? 'BSCS';
            $stuId = sprintf('STU-2026-%04d', $number);

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Student Active {$number}",
                    'student_id' => $stuId,
                    'program' => $this->programLabel($progCode),
                    'year_level' => '3rd',
                    'password' => Hash::make($this->passwordFor($email)),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subDays(10),
                    'email_verified_at' => now()->subDays(10),
                    'user_type' => UserType::Student,
                ],
            );

            $user->syncRoles('student');

            $prog = Program::query()->where('code', $progCode)->first();
            if ($prog) {
                StudentProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'program_id' => $prog->id,
                        'student_number' => $stuId,
                        'year_level' => 3,
                    ],
                );
            }
        }
    }

    private function seedTestStudentAccount(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'student.test@ndmu.edu.ph'],
            [
                'name' => 'Test Student Researcher',
                'student_id' => 'STU-TEST-0001',
                'program' => $this->programLabel('BSCS'),
                'password' => Hash::make($this->passwordFor('student.test@ndmu.edu.ph')),
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'user_type' => UserType::Student,
            ],
        );

        $user->syncRoles('student');

        $prog = Program::query()->where('code', 'BSCS')->first();
        if ($prog) {
            StudentProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'program_id' => $prog->id,
                    'student_number' => 'STU-TEST-0001',
                    'year_level' => 3,
                ],
            );
        }
    }

    private function passwordFor(string $email): string
    {
        foreach (self::LOGIN_ACCOUNTS as $account) {
            if ($account['email'] === $email) {
                return $account['password'];
            }
        }

        return 'Password!12345';
    }

    private function programLabel(string $code): string
    {
        $program = collect(config('academic.programs'))->firstWhere('code', $code);

        if (! is_array($program)) {
            throw new \LogicException("Academic program {$code} is not configured.");
        }

        return $program['label'] ?? $program['name'];
    }
}

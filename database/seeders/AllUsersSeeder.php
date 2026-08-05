<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AllUsersSeeder extends Seeder
{
    /**
     * Local/testing login credentials. Keep one account per line for easy reference.
     *
     * The private bootstrap administrator is configured separately in .env using
     * ADMIN_EMAIL and ADMIN_PASSWORD and must never be committed here.
     */
    private const LOGIN_ACCOUNTS = [
        ['role' => 'Student (test)', 'email' => 'student.test@ndmu.edu.ph', 'password' => 'TestOnly!2345'],
        ['role' => 'System administrator', 'email' => 'admin@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'College dean', 'email' => 'l.castillo@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Research facilitator', 'email' => 'r.dela-paz@ndmu.edu.ph', 'password' => ''],
        ['role' => 'Research facilitator', 'email' => 'j.montero@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Research adviser', 'email' => 'r.garcia@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Research adviser', 'email' => 'm.tan@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Research adviser', 'email' => 'l.fernandez@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Research adviser', 'email' => 'b.ramos@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Panelist', 'email' => 'p.cruz@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Panelist', 'email' => 'a.santos@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (pending)', 'email' => 'juan.delacruz@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (pending)', 'email' => 'ana.reyes@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (pending)', 'email' => 'kevin.aguila@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (pending)', 'email' => 'clara.nieto@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (pending)', 'email' => 'dante.flores@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active1@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active2@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active3@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active4@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active5@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active6@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active7@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active8@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active9@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active10@ndmu.edu.ph', 'password' => 'Password!12345'],
        ['role' => 'Student (active)', 'email' => 'student.active11@ndmu.edu.ph', 'password' => 'Password!12345'],
    ];

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->seedBootstrapAdministrator();

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('Demo and test accounts were skipped outside the local/testing environment.');

            return;
        }

        $this->seedStaffAccounts();
        $this->seedPendingStudentAccounts();
        $this->seedActiveStudentAccounts();
        $this->seedTestStudentAccount();

        $this->command?->info('All local user accounts were seeded successfully.');
        $this->command?->info('Login credentials are listed at the top of AllUsersSeeder.php.');
        $this->command?->warn('These shared accounts are for local development and testing only.');
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
        $college = config('academic.college.name');

        $accounts = [
            ['System Administrator', 'admin@ndmu.edu.ph', 'system-administrator', $college],
            ['Dr. Lourdes Castillo', 'l.castillo@ndmu.edu.ph', 'college-dean', $college],
            ['Dr. Rosario Dela Paz', 'r.dela-paz@ndmu.edu.ph', 'research-facilitator', $college],
            ['Engr. Jose Montero', 'j.montero@ndmu.edu.ph', 'research-facilitator', $college],
            ['Dr. Reyna Garcia', 'r.garcia@ndmu.edu.ph', 'research-adviser', $college],
            ['Dr. Michael Tan', 'm.tan@ndmu.edu.ph', 'research-adviser', $college],
            ['Prof. Lucia Fernandez', 'l.fernandez@ndmu.edu.ph', 'research-adviser', $college],
            ['Dr. Benjamin Ramos', 'b.ramos@ndmu.edu.ph', 'research-adviser', $college],
            ['Prof. Patricia Cruz', 'p.cruz@ndmu.edu.ph', 'panelist', $college],
            ['Dr. Antonio Santos', 'a.santos@ndmu.edu.ph', 'panelist', $college],
        ];

        foreach ($accounts as [$name, $email, $role, $department]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($this->passwordFor($email)),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subMonths(6),
                    'email_verified_at' => now()->subMonths(6),
                    'department' => $department,
                    'user_type' => $role === 'system-administrator' ? UserType::Admin : UserType::Faculty,
                ],
            );

            $this->assignMigratedRoles($user, $role);
        }
    }

    private function seedPendingStudentAccounts(): void
    {
        $accounts = [
            ['Juan Dela Cruz', 'juan.delacruz@ndmu.edu.ph', 'STU-2026-0051', $this->programLabel('BSCE'), '3rd'],
            ['Ana Reyes', 'ana.reyes@ndmu.edu.ph', 'STU-2026-0052', $this->programLabel('BSIT'), '4th'],
            ['Kevin Aguila', 'kevin.aguila@ndmu.edu.ph', 'STU-2026-0053', $this->programLabel('BSARCH'), '2nd'],
            ['Clara Nieto', 'clara.nieto@ndmu.edu.ph', 'STU-2026-0054', $this->programLabel('BSCS'), '3rd'],
            ['Dante Flores', 'dante.flores@ndmu.edu.ph', 'STU-2026-0055', $this->programLabel('BSECE'), '4th'],
        ];

        foreach ($accounts as [$name, $email, $studentId, $program, $yearLevel]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'student_id' => $studentId,
                    'program' => $program,
                    'year_level' => $yearLevel,
                    'password' => Hash::make($this->passwordFor($email)),
                    'status' => AccountStatus::Pending,
                    'approved_at' => null,
                    'email_verified_at' => now()->subDays(5),
                    'user_type' => UserType::Student,
                ],
            );

            $user->syncRoles('student');
        }
    }

    private function seedActiveStudentAccounts(): void
    {
        for ($number = 1; $number <= 11; $number++) {
            $email = "student.active{$number}@ndmu.edu.ph";

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Student Active {$number}",
                    'student_id' => sprintf('STU-2026-%04d', $number),
                    'program' => $this->programLabel('BSCS'),
                    'year_level' => '3rd',
                    'password' => Hash::make($this->passwordFor($email)),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subDays(10),
                    'email_verified_at' => now()->subDays(10),
                    'user_type' => UserType::Student,
                ],
            );

            $user->syncRoles('student');
        }
    }

    private function seedTestStudentAccount(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'student.test@ndmu.edu.ph'],
            [
                'name' => 'Test Student Researcher',
                'program' => $this->programLabel('BSCS'),
                'password' => Hash::make($this->passwordFor('student.test@ndmu.edu.ph')),
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'user_type' => UserType::Student,
            ],
        );

        $user->syncRoles('student');
    }

    private function assignMigratedRoles(User $user, string $legacyRole): void
    {
        $canonical = config("access-control.legacy_role_aliases.{$legacyRole}", $legacyRole);

        if ($canonical === 'administrator') {
            $user->syncRoles($canonical);

            return;
        }

        $user->syncRoles(array_values(array_unique(['faculty', $canonical])));
    }

    private function passwordFor(string $email): string
    {
        foreach (self::LOGIN_ACCOUNTS as $account) {
            if ($account['email'] === $email) {
                return $account['password'];
            }
        }

        throw new \LogicException("No local login credentials are configured for {$email}.");
    }

    private function programLabel(string $code): string
    {
        $program = collect(config('academic.programs'))->firstWhere('code', $code);

        if (! is_array($program)) {
            throw new \LogicException("Academic program {$code} is not configured.");
        }

        return $program['label'];
    }
}

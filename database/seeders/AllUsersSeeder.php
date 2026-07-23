<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
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
        ['role' => 'Research facilitator', 'email' => 'r.dela-paz@ndmu.edu.ph', 'password' => 'Password!12345'],
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
            ],
        );

        $administrator->syncRoles('system-administrator');
    }

    private function seedStaffAccounts(): void
    {
        $accounts = [
            ['System Administrator', 'admin@ndmu.edu.ph', 'system-administrator', 'Information Technology'],
            ['Dr. Lourdes Castillo', 'l.castillo@ndmu.edu.ph', 'college-dean', 'Office of the College Dean'],
            ['Dr. Rosario Dela Paz', 'r.dela-paz@ndmu.edu.ph', 'research-facilitator', 'College of Information Technology'],
            ['Engr. Jose Montero', 'j.montero@ndmu.edu.ph', 'research-facilitator', 'College of Engineering'],
            ['Dr. Reyna Garcia', 'r.garcia@ndmu.edu.ph', 'research-adviser', 'College of Information Technology'],
            ['Dr. Michael Tan', 'm.tan@ndmu.edu.ph', 'research-adviser', 'College of Information Technology'],
            ['Prof. Lucia Fernandez', 'l.fernandez@ndmu.edu.ph', 'research-adviser', 'College of Engineering'],
            ['Dr. Benjamin Ramos', 'b.ramos@ndmu.edu.ph', 'research-adviser', 'College of Information Technology'],
            ['Prof. Patricia Cruz', 'p.cruz@ndmu.edu.ph', 'panelist', 'College of Engineering'],
            ['Dr. Antonio Santos', 'a.santos@ndmu.edu.ph', 'panelist', 'College of Information Technology'],
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
                ],
            );

            $user->syncRoles($role);
        }
    }

    private function seedPendingStudentAccounts(): void
    {
        $accounts = [
            ['Juan Dela Cruz', 'juan.delacruz@ndmu.edu.ph', 'STU-2026-0051', 'Bachelor of Science in Civil Engineering (BS CE)', '3rd'],
            ['Ana Reyes', 'ana.reyes@ndmu.edu.ph', 'STU-2026-0052', 'Bachelor of Science in Information Technology (BSIT)', '4th'],
            ['Kevin Aguila', 'kevin.aguila@ndmu.edu.ph', 'STU-2026-0053', 'Bachelor of Science in Architecture (BS Arch)', '2nd'],
            ['Clara Nieto', 'clara.nieto@ndmu.edu.ph', 'STU-2026-0054', 'Bachelor of Science in Computer Science (BSCS)', '3rd'],
            ['Dante Flores', 'dante.flores@ndmu.edu.ph', 'STU-2026-0055', 'Bachelor of Science in Electronics Engineering (BS EcE)', '4th'],
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
                ],
            );

            $user->syncRoles('student-researcher');
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
                    'program' => 'Bachelor of Science in Computer Science (BSCS)',
                    'year_level' => '3rd',
                    'password' => Hash::make($this->passwordFor($email)),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subDays(10),
                    'email_verified_at' => now()->subDays(10),
                ],
            );

            $user->syncRoles('student-researcher');
        }
    }

    private function seedTestStudentAccount(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'student.test@ndmu.edu.ph'],
            [
                'name' => 'Test Student Researcher',
                'password' => Hash::make($this->passwordFor('student.test@ndmu.edu.ph')),
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles('student-researcher');
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
}

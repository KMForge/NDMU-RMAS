<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $college = config('academic.college.name');

        // 1. Staff and Admin Users (Active)
        $staff = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@ndmu.edu.ph',
                'role' => 'system-administrator',
                'department' => $college,
            ],
            [
                'name' => 'Dr. Lourdes Castillo',
                'email' => 'l.castillo@ndmu.edu.ph',
                'role' => 'college-dean',
                'department' => $college,
            ],
            [
                'name' => 'Dr. Rosario Dela Paz',
                'email' => 'r.dela-paz@ndmu.edu.ph',
                'role' => 'research-facilitator',
                'department' => $college,
            ],
            [
                'name' => 'Engr. Jose Montero',
                'email' => 'j.montero@ndmu.edu.ph',
                'role' => 'research-facilitator',
                'department' => $college,
            ],
            [
                'name' => 'Dr. Reyna Garcia',
                'email' => 'r.garcia@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => $college,
            ],
            [
                'name' => 'Dr. Michael Tan',
                'email' => 'm.tan@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => $college,
            ],
            [
                'name' => 'Prof. Lucia Fernandez',
                'email' => 'l.fernandez@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => $college,
            ],
            [
                'name' => 'Dr. Benjamin Ramos',
                'email' => 'b.ramos@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => $college,
            ],
            [
                'name' => 'Prof. Patricia Cruz',
                'email' => 'p.cruz@ndmu.edu.ph',
                'role' => 'panelist',
                'department' => $college,
            ],
            [
                'name' => 'Dr. Antonio Santos',
                'email' => 'a.santos@ndmu.edu.ph',
                'role' => 'panelist',
                'department' => $college,
            ],
        ];

        foreach ($staff as $s) {
            $user = User::query()->updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('Password!12345'),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subMonths(6),
                    'email_verified_at' => now()->subMonths(6),
                    'department' => $s['department'],
                ]
            );
            $user->syncRoles($s['role']);
        }

        // 2. Pending Student Researchers
        $students = [
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.delacruz@ndmu.edu.ph',
                'student_id' => 'STU-2026-0051',
                'program' => $this->programLabel('BSCE'),
                'year_level' => '3rd',
                'created_at' => now()->subDays(5),
            ],
            [
                'name' => 'Ana Reyes',
                'email' => 'ana.reyes@ndmu.edu.ph',
                'student_id' => 'STU-2026-0052',
                'program' => $this->programLabel('BSIT'),
                'year_level' => '4th',
                'created_at' => now()->subDays(3),
            ],
            [
                'name' => 'Kevin Aguila',
                'email' => 'kevin.aguila@ndmu.edu.ph',
                'student_id' => 'STU-2026-0053',
                'program' => $this->programLabel('BSARCH'),
                'year_level' => '2nd',
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Clara Nieto',
                'email' => 'clara.nieto@ndmu.edu.ph',
                'student_id' => 'STU-2026-0054',
                'program' => $this->programLabel('BSCS'),
                'year_level' => '3rd',
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Dante Flores',
                'email' => 'dante.flores@ndmu.edu.ph',
                'student_id' => 'STU-2026-0055',
                'program' => $this->programLabel('BSECE'),
                'year_level' => '4th',
                'created_at' => now()->subDay(),
            ],
        ];

        foreach ($students as $st) {
            $user = User::query()->updateOrCreate(
                ['email' => $st['email']],
                [
                    'name' => $st['name'],
                    'student_id' => $st['student_id'],
                    'program' => $st['program'],
                    'year_level' => $st['year_level'],
                    'password' => Hash::make('Password!12345'),
                    'status' => AccountStatus::Pending,
                    'approved_at' => null,
                    'email_verified_at' => now()->subDays(5),
                    'created_at' => $st['created_at'],
                ]
            );
            $user->syncRoles('student-researcher');
        }

        // Add 11 more generic active student researchers so total users is 26 (10 staff + 5 pending + 11 active students)
        // This is so the UI total matches exactly the "26" in the mockup!
        for ($i = 1; $i <= 11; $i++) {
            $email = "student.active{$i}@ndmu.edu.ph";
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Student Active {$i}",
                    'student_id' => 'STU-2026-000'.$i,
                    'program' => $this->programLabel('BSCS'),
                    'year_level' => '3rd',
                    'password' => Hash::make('Password!12345'),
                    'status' => AccountStatus::Active,
                    'approved_at' => now()->subDays(10),
                    'email_verified_at' => now()->subDays(10),
                ]
            );
            $user->syncRoles('student-researcher');
        }
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

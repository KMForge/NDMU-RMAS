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
        // 1. Staff and Admin Users (Active)
        $staff = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@ndmu.edu.ph',
                'role' => 'system-administrator',
                'department' => 'Information Technology',
            ],
            [
                'name' => 'Dr. Lourdes Castillo',
                'email' => 'l.castillo@ndmu.edu.ph',
                'role' => 'college-dean',
                'department' => 'Office of the College Dean',
            ],
            [
                'name' => 'Dr. Rosario Dela Paz',
                'email' => 'r.dela-paz@ndmu.edu.ph',
                'role' => 'research-facilitator',
                'department' => 'College of Information Technology',
            ],
            [
                'name' => 'Engr. Jose Montero',
                'email' => 'j.montero@ndmu.edu.ph',
                'role' => 'research-facilitator',
                'department' => 'College of Engineering',
            ],
            [
                'name' => 'Dr. Reyna Garcia',
                'email' => 'r.garcia@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => 'College of Information Technology',
            ],
            [
                'name' => 'Dr. Michael Tan',
                'email' => 'm.tan@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => 'College of Information Technology',
            ],
            [
                'name' => 'Prof. Lucia Fernandez',
                'email' => 'l.fernandez@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => 'College of Engineering',
            ],
            [
                'name' => 'Dr. Benjamin Ramos',
                'email' => 'b.ramos@ndmu.edu.ph',
                'role' => 'research-adviser',
                'department' => 'College of Information Technology',
            ],
            [
                'name' => 'Prof. Patricia Cruz',
                'email' => 'p.cruz@ndmu.edu.ph',
                'role' => 'panelist',
                'department' => 'College of Engineering',
            ],
            [
                'name' => 'Dr. Antonio Santos',
                'email' => 'a.santos@ndmu.edu.ph',
                'role' => 'panelist',
                'department' => 'College of Information Technology',
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
                'program' => 'Bachelor of Science in Civil Engineering (BS CE)',
                'year_level' => '3rd',
                'created_at' => now()->subDays(5),
            ],
            [
                'name' => 'Ana Reyes',
                'email' => 'ana.reyes@ndmu.edu.ph',
                'student_id' => 'STU-2026-0052',
                'program' => 'Bachelor of Science in Information Technology (BSIT)',
                'year_level' => '4th',
                'created_at' => now()->subDays(3),
            ],
            [
                'name' => 'Kevin Aguila',
                'email' => 'kevin.aguila@ndmu.edu.ph',
                'student_id' => 'STU-2026-0053',
                'program' => 'Bachelor of Science in Architecture (BS Arch)',
                'year_level' => '2nd',
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Clara Nieto',
                'email' => 'clara.nieto@ndmu.edu.ph',
                'student_id' => 'STU-2026-0054',
                'program' => 'Bachelor of Science in Computer Science (BSCS)',
                'year_level' => '3rd',
                'created_at' => now()->subDays(2),
            ],
            [
                'name' => 'Dante Flores',
                'email' => 'dante.flores@ndmu.edu.ph',
                'student_id' => 'STU-2026-0055',
                'program' => 'Bachelor of Science in Electronics Engineering (BS EcE)',
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
                    'program' => 'Bachelor of Science in Computer Science (BSCS)',
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
}

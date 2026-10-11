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

/** Adds only evaluation students 11–20; never re-seeds existing staff or students. */
class AdditionalCsdEvaluationStudentSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->with('department')->where('code', 'BSIT')
            ->whereHas('department', fn ($query) => $query->where('code', 'CSD'))->firstOrFail();
        $role = Role::query()->where('name', 'student')->where('guard_name', 'web')->firstOrFail();
        $programLabel = collect(config('academic.programs'))->firstWhere('code', 'BSIT')['label'] ?? $program->name;
        $created = DB::transaction(function () use ($program, $role, $programLabel): int {
            $created = 0;
            foreach (range(11, 20) as $number) {
                $email = "student{$number}@ndmu.edu.ph";
                // Re-running must not reset passwords, names, roles, or signatures.
                if (User::query()->where('email', $email)->exists()) {
                    continue;
                }
                $studentNumber = sprintf('STU-CSD-EVAL-%03d', $number);
                if (User::query()->where('student_id', $studentNumber)->exists()
                    || StudentProfile::query()->where('student_number', $studentNumber)->exists()) {
                    throw new LogicException("Student number {$studentNumber} already belongs to another account.");
                }
                $user = User::query()->create([
                    'name' => "BSIT Student {$number}", 'first_name' => 'Student', 'last_name' => (string) $number,
                    'email' => $email, 'student_id' => $studentNumber,
                    'password' => Hash::make(CsdEvaluationUserSeeder::PASSWORD),
                    'user_type' => UserType::Student, 'status' => AccountStatus::Active,
                    'approved_at' => now(), 'email_verified_at' => now(),
                    'program' => $programLabel, 'department' => $program->department->name, 'year_level' => '4th',
                ]);
                $user->assignRole($role);
                StudentProfile::query()->create([
                    'user_id' => $user->id, 'program_id' => $program->id,
                    'student_number' => $studentNumber, 'year_level' => 4,
                ]);
                $created++;
            }

            return $created;
        });

        $this->command?->info("Created {$created} additional BSIT/CSD evaluation student accounts (11–20). Existing accounts were left unchanged.");
    }
}

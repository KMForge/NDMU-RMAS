<?php

namespace App\Modules\Registration\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RegisterStudent
{
    /**
     * @param  array{student_id: string, name: string, email: string, program: string, year_level: int, password: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        if (! SystemSetting::query()->value('student_registration_enabled')) {
            throw ValidationException::withMessages([
                'email' => 'Student registration is currently unavailable.',
            ]);
        }

        $studentRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'student')
            ->first();

        if ($studentRole === null) {
            throw ValidationException::withMessages([
                'email' => 'Student registration is not configured. Please contact the system administrator.',
            ]);
        }

        return DB::transaction(function () use ($attributes, $studentRole): User {
            $student = User::query()->create([
                'student_id' => $attributes['student_id'],
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'program' => $attributes['program'],
                'year_level' => (string) $attributes['year_level'],
                'department' => (string) config('academic.college.name'),
                'password' => $attributes['password'],
                'status' => AccountStatus::Pending,
                'approved_at' => null,
                'email_verified_at' => null,
                'user_type' => UserType::Student,
            ]);

            $student->assignRole($studentRole);

            return $student;
        });
    }
}

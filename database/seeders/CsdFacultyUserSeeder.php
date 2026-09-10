<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class CsdFacultyUserSeeder extends Seeder
{
    public const PASSWORD = 'Password!12345';

    /**
     * @var list<array{
     *     name: string,
     *     email: string,
     *     roles: list<string>,
     *     emp_no: string,
     *     rank: string,
     * }>
     */
    public const FACULTY = [
        [
            'name' => 'Vince Marc B. Sabado',
            'email' => 'vm.sabado@ndmu.edu.ph',
            'roles' => ['research-facilitator'],
            'emp_no' => 'EMP-CSD-0101',
            'rank' => 'Assistant Professor / Research Facilitator',
        ],
        [
            'name' => 'Aliah Chavy B. Sabado',
            'email' => 'ac.sabado@ndmu.edu.ph',
            'roles' => ['thesis-adviser', 'panel-member'],
            'emp_no' => 'EMP-CSD-0102',
            'rank' => 'Assistant Professor / Thesis Adviser / Panel Member',
        ],
        [
            'name' => 'Brenda M. Balala',
            'email' => 'b.balala@ndmu.edu.ph',
            'roles' => ['thesis-adviser', 'panel-member'],
            'emp_no' => 'EMP-CSD-0103',
            'rank' => 'Assistant Professor / Thesis Adviser / Panel Member',
        ],
        [
            'name' => 'Merch Jay P. Dollaga',
            'email' => 'mj.dollaga@ndmu.edu.ph',
            'roles' => ['thesis-adviser', 'panel-member'],
            'emp_no' => 'EMP-CSD-0104',
            'rank' => 'Assistant Professor / Thesis Adviser / Panel Member',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('CSD faculty user accounts were skipped outside the local/testing environment.');

            return;
        }

        $csd = Department::query()->where('code', 'CSD')->first();

        if ($csd === null) {
            throw new LogicException('Seed the academic structure before seeding CSD faculty accounts.');
        }

        $departmentName = $csd->name ?? 'Computer Studies Department';

        foreach (self::FACULTY as $facultyData) {
            $user = User::query()->updateOrCreate(
                ['email' => $facultyData['email']],
                [
                    'name' => $facultyData['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'status' => AccountStatus::Active,
                    'approved_at' => now(),
                    'email_verified_at' => now(),
                    'department' => $departmentName,
                    'user_type' => UserType::Faculty,
                ],
            );

            StudentProfile::query()->where('user_id', $user->id)->delete();

            $user->syncRoles($facultyData['roles']);

            FacultyProfile::query()
                ->where('employee_number', $facultyData['emp_no'])
                ->where('user_id', '!=', $user->id)
                ->update(['employee_number' => $facultyData['emp_no'].'-OLD-'.$user->id]);

            FacultyProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => $csd->id,
                    'employee_number' => $facultyData['emp_no'],
                    'academic_rank' => $facultyData['rank'],
                    'specialization' => $departmentName,
                ],
            );
        }

        $this->command?->info('CSD faculty user accounts seeded successfully.');
    }
}

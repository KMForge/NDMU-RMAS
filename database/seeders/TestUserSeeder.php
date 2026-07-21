<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('TestUserSeeder can only run in local or testing environments.');
        }

        if (! Role::query()->where('name', 'student-researcher')->where('guard_name', 'web')->exists()
            || ! Permission::query()->where('name', 'research.view-own')->where('guard_name', 'web')->exists()) {
            $this->call(RolePermissionSeeder::class);
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'student.test@ndmu.edu.ph'],
            [
                'name' => 'Test Student Researcher',
                'password' => Hash::make('TestOnly!2345'),
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles('student-researcher');

        $this->command?->info('Test user created: student.test@ndmu.edu.ph / TestOnly!2345');
        $this->command?->warn('Use this account only for local development and testing.');
    }
}

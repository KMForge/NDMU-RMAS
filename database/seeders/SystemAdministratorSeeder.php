<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAdministratorSeeder extends Seeder
{
    public function run(): void
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
}

<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\AllUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AllUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_local_user_account_types(): void
    {
        $this->seed(AllUsersSeeder::class);

        $admin = User::query()->where('email', 'admin@ndmu.edu.ph')->firstOrFail();
        $dean = User::query()->where('email', 'l.castillo@ndmu.edu.ph')->firstOrFail();
        $pendingStudent = User::query()->where('email', 'juan.delacruz@ndmu.edu.ph')->firstOrFail();
        $activeStudent = User::query()->where('email', 'student.active11@ndmu.edu.ph')->firstOrFail();
        $testStudent = User::query()->where('email', 'student.test@ndmu.edu.ph')->firstOrFail();

        $this->assertTrue($admin->hasRole('administrator'));
        $this->assertSame(UserType::Admin, $admin->user_type);
        $this->assertTrue($dean->hasExactRoles(['dean']));
        $this->assertSame(UserType::Faculty, $dean->user_type);
        $this->assertSame(AccountStatus::Pending, $pendingStudent->status);
        $this->assertNull($pendingStudent->approved_at);
        $this->assertSame(AccountStatus::Active, $activeStudent->status);
        $this->assertTrue($activeStudent->hasRole('student'));
        $this->assertSame(UserType::Student, $activeStudent->user_type);
        $this->assertTrue(Hash::check('Password!12345', $admin->password));
        $this->assertTrue(Hash::check('TestOnly!2345', $testStudent->password));
    }
}

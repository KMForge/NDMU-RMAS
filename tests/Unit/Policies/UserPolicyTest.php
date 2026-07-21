<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_user_manager_can_manage_another_users_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole('system-administrator');
        $student = User::factory()->create();
        $policy = new UserPolicy;

        $this->assertTrue($policy->manageRoles($administrator, $student));
        $this->assertFalse($policy->manageRoles($student, $administrator));
        $this->assertFalse($policy->manageRoles($administrator, $administrator));
    }
}

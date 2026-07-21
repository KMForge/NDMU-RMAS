<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_roles_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_system_administrator_has_explicit_settings_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $administrator = User::factory()->create();
        $administrator->assignRole('system-administrator');

        $this->assertTrue($administrator->can('settings.manage'));
        $this->actingAs($administrator)->get(route('admin.dashboard'))->assertOk();
    }
}

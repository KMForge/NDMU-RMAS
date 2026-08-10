<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DynamicRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_type_does_not_grant_dashboard_access_without_permission(): void
    {
        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);

        $this->actingAs($faculty)
            ->get(route('adviser.dashboard'))
            ->assertForbidden();
    }

    public function test_custom_role_can_grant_a_workspace_without_hard_coded_role_name(): void
    {
        $role = Role::create([
            'name' => 'temporary-thesis-mentor',
            'guard_name' => 'web',
            'display_name' => 'Temporary Thesis Mentor',
        ]);
        $role->givePermissionTo(['dashboards.adviser.view', 'research.view-assigned']);

        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);
        $faculty->assignRole($role);

        $this->actingAs($faculty)
            ->get(route('adviser.dashboard'))
            ->assertOk();
    }

    public function test_faculty_user_can_hold_multiple_responsibility_roles(): void
    {
        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);
        $faculty->assignRole(['program-coordinator', 'thesis-adviser']);

        $this->assertTrue($faculty->hasAllRoles(['program-coordinator', 'thesis-adviser']));
        $this->assertTrue($faculty->can('dashboards.facilitator.view'));
        $this->assertTrue($faculty->can('dashboards.adviser.view'));
    }

    public function test_permission_catalog_is_saved_with_metadata(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => 'classes.manage-groups',
            'display_name' => 'Manage Research Groups',
            'module' => 'Classes',
        ]);
    }
}

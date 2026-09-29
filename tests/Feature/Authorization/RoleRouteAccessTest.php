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

    public function test_dean_dashboard_restores_the_tab_from_the_url(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $dean = User::factory()->create();
        $dean->assignRole('dean');

        $this->actingAs($dean)
            ->get(route('dean.dashboard', ['tab' => 'repository']))
            ->assertOk()
            ->assertSee('College Document Repository')
            ->assertSee('bg-[#eebc3f]', false);
    }

    public function test_every_dean_dashboard_tab_renders_without_a_server_error(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $dean = User::factory()->create();
        $dean->assignRole('dean');

        foreach (['dashboard', 'pending', 'manuscript', 'schedule', 'repository', 'notifications', 'settings'] as $tab) {
            $this->actingAs($dean)
                ->get(route('dean.dashboard', ['tab' => $tab]))
                ->assertOk();
        }
    }

    public function test_empty_dean_approval_queue_displays_a_clear_empty_state(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $dean = User::factory()->create();
        $dean->assignRole('dean');

        $this->actingAs($dean)
            ->get(route('dean.dashboard', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('No approvals waiting')
            ->assertSee('Open Forms Workspace');
    }

    public function test_dean_dashboard_does_not_expose_prototype_faculty_appointments(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $dean = User::factory()->create();
        $dean->assignRole('dean');

        $this->actingAs($dean)
            ->get(route('dean.dashboard', ['tab' => 'appointments']))
            ->assertOk()
            ->assertSee('College Research Overview')
            ->assertDontSee('Faculty Appointments')
            ->assertDontSee('New Advisor Appointment')
            ->assertDontSee('Register New User')
            ->assertDontSee('AI-Powered Traffic Management System')
            ->assertDontSee('Machine Learning for Crop Disease Detection');
    }
}

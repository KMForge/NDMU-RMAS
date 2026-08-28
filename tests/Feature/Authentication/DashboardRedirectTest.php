<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_dashboard_entry_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_is_redirected_from_login_to_dashboard_entry(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_entry_redirects_user_to_their_role_dashboard(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertRedirect(route('student.dashboard'));
    }

    public function test_highest_privileged_role_is_used_for_multi_role_account(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole(['student-researcher', 'system-administrator']);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_welcome_page_always_shows_login_and_register_navigation(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Register');

        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Register');
    }

    public function test_public_dashboard_preview_routes_are_not_available(): void
    {
        $this->get('/preview/student-dashboard')->assertNotFound();
        $this->get('/preview/admin-dashboard')->assertNotFound();
    }
}

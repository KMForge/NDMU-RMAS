<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolelessFacultyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_roleless_faculty_is_sent_to_access_pending_and_cannot_open_workspaces(): void
    {
        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertRedirect(route('access.pending'));

        $this->actingAs($faculty)
            ->get(route('access.pending'))
            ->assertOk()
            ->assertSee('Access assignment pending');

        $this->actingAs($faculty)
            ->get(route('adviser.dashboard'))
            ->assertForbidden();
    }

    public function test_assigning_a_workspace_role_removes_the_pending_access_state(): void
    {
        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);
        $faculty->assignRole('thesis-adviser');

        $this->actingAs($faculty)
            ->get(route('access.pending'))
            ->assertRedirect(route('adviser.dashboard'));
    }
}

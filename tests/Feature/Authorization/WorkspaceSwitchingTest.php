<?php

namespace Tests\Feature\Authorization;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Authorization\Services\ResolveUserDashboard;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_role_user_can_switch_to_an_authorized_workspace_and_the_action_is_audited(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(['faculty', 'research-facilitator', 'thesis-adviser']);

        $response = $this->actingAs($user)
            ->withSession(['active_workspace' => 'facilitator'])
            ->post(route('workspace.switch', 'adviser'));

        $response
            ->assertRedirect(route('adviser.dashboard'))
            ->assertSessionHas('active_workspace', 'adviser');

        $auditLog = AuditLog::query()->sole();

        $this->assertSame($user->id, $auditLog->user_id);
        $this->assertSame($user->name, $auditLog->actor_name);
        $this->assertSame($user->email, $auditLog->actor_email);
        $this->assertSame($user->name, $auditLog->subject_name);
        $this->assertSame($user->email, $auditLog->subject_email);
        $this->assertSame('workspace.switched', $auditLog->event);
        $this->assertSame(['workspace' => 'facilitator'], $auditLog->old_values);
        $this->assertSame(['workspace' => 'adviser'], $auditLog->new_values);
    }

    public function test_user_cannot_switch_to_a_workspace_without_its_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($student)
            ->withSession(['active_workspace' => 'student'])
            ->post(route('workspace.switch', 'admin'))
            ->assertForbidden();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_workspace_catalog_contains_only_permission_authorized_workspaces(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(['faculty', 'research-facilitator', 'thesis-adviser']);

        $workspaces = app(ResolveUserDashboard::class)->workspacesFor($user);

        $this->assertSame(['facilitator', 'adviser'], array_keys($workspaces));
        $this->assertArrayNotHasKey('admin', $workspaces);
        $this->assertArrayNotHasKey('student', $workspaces);
    }

    public function test_multi_role_dashboard_renders_the_switcher_and_tracks_the_current_workspace(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(['faculty', 'research-facilitator', 'thesis-adviser']);

        $this->actingAs($user)
            ->get(route('facilitator.dashboard'))
            ->assertOk()
            ->assertSee('Switch Workspace')
            ->assertSee('Research Facilitator')
            ->assertSee('Thesis Adviser')
            ->assertSessionHas('active_workspace', 'facilitator');
    }

    public function test_guest_cannot_use_the_workspace_switch_endpoint(): void
    {
        $this->post(route('workspace.switch', 'adviser'))
            ->assertRedirect(route('login'));
    }
}

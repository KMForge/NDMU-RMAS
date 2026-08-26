<?php

namespace Tests\Feature\AuditLogs;

use App\Enums\AccountStatus;
use App\Livewire\AdminDashboard;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogViewerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dashboard_access_without_audit_permission_cannot_render_or_retrieve_audit_data(): void
    {
        $role = Role::query()->create(['name' => 'limited-admin', 'guard_name' => 'web']);
        $role->givePermissionTo(['dashboards.admin.view', 'users.manage']);
        $user = User::factory()->create();
        $user->assignRole($role);
        AuditLog::query()->create(['event' => 'user.created', 'description' => 'Sensitive audit evidence.', 'created_at' => now()]);

        $this->actingAs($user)->get(route('admin.dashboard', ['tab' => 'audit']))->assertForbidden();

        Livewire::actingAs($user)->test(AdminDashboard::class)
            ->assertDontSee('Sensitive audit evidence.')
            ->set('tab', 'audit')
            ->assertForbidden();
    }

    public function test_authorized_viewer_can_filter_by_event_context_and_outcome(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        AuditLog::query()->create([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'event' => 'workspace.switched',
            'actor_context' => 'adviser',
            'outcome' => 'succeeded',
            'description' => 'Visible evidence.',
            'created_at' => now(),
        ]);
        AuditLog::query()->create([
            'event' => 'auth.login.failed',
            'outcome' => 'denied',
            'description' => 'Hidden by filters.',
            'created_at' => now()->subSecond(),
        ]);

        $component = Livewire::actingAs($admin)->withQueryParams(['tab' => 'audit'])->test(AdminDashboard::class)
            ->set('auditEvent', 'workspace.switched')
            ->set('auditContext', 'adviser')
            ->set('auditOutcome', 'succeeded');

        $this->assertSame(['Visible evidence.'], $component->viewData('auditLogs')->pluck('description')->all());
    }

    public function test_unverified_and_inactive_users_cannot_open_audit_viewer(): void
    {
        $unverified = User::factory()->create(['email_verified_at' => null]);
        $unverified->assignRole('system-administrator');
        $this->actingAs($unverified)->get(route('admin.dashboard', ['tab' => 'audit']))->assertRedirect();

        $inactive = User::factory()->create(['status' => AccountStatus::Suspended]);
        $inactive->assignRole('system-administrator');
        $this->actingAs($inactive)->get(route('admin.dashboard', ['tab' => 'audit']))->assertForbidden();
    }

    public function test_tampered_filter_values_fail_closed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        AuditLog::query()->create([
            'event' => 'workspace.switched',
            'outcome' => 'succeeded',
            'description' => 'Must not be returned for an invalid filter.',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($admin)
            ->withQueryParams(['tab' => 'audit'])
            ->test(AdminDashboard::class)
            ->set('auditEvent', 'not-an-allowed-event');

        $this->assertCount(0, $component->viewData('auditLogs'));
    }

    public function test_no_audit_mutation_route_exists(): void
    {
        $mutationRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_contains($route->uri(), 'audit') && collect($route->methods())->intersect(['POST', 'PUT', 'PATCH', 'DELETE'])->isNotEmpty());

        $this->assertCount(0, $mutationRoutes);
    }
}

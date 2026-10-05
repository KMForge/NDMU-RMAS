<?php

namespace Tests\Feature\Authentication;

use App\Enums\UserType;
use App\Http\Middleware\ThrottleRequestsUnlessHighTrafficMode;
use App\Livewire\AdminDashboard;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TemporaryPasswordOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ThrottleRequests::class, ThrottleRequestsUnlessHighTrafficMode::class]);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_created_staff_receives_generated_temporary_password_shown_once(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $component = Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->set('name', 'Generated Password Faculty')
            ->set('email', 'generated.faculty@ndmu.edu.ph')
            ->set('department', 'CSD')
            ->call('createStaffAccount')
            ->assertHasNoErrors();

        $temporaryPassword = $component->get('issuedTemporaryPassword');
        $staff = User::query()->where('email', 'generated.faculty@ndmu.edu.ph')->firstOrFail();

        $this->assertIsString($temporaryPassword);
        $this->assertGreaterThanOrEqual(12, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertNotNull($staff->temporary_password_expires_at);

        $component->call('closeTemporaryPassword')->assertSet('issuedTemporaryPassword', null);
    }

    public function test_staff_is_forced_to_replace_temporary_password_before_workspace_access(): void
    {
        $staff = User::factory()->create([
            'email' => 'first.login@ndmu.edu.ph',
            'password' => 'Temporary!2345',
            'user_type' => UserType::Faculty,
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHour(),
        ]);
        $staff->assignRole('research-facilitator');

        $this->post(route('login.store'), [
            'email' => $staff->email,
            'password' => 'Temporary!2345',
        ])->assertRedirect(route('password.change-required'));

        $this->get(route('facilitator.dashboard'))
            ->assertRedirect(route('password.change-required'));

        $this->put(route('password.change-required.update'), [
            'password' => 'Permanent!Password2345',
            'password_confirmation' => 'Permanent!Password2345',
        ])->assertRedirect(route('facilitator.dashboard'));

        $staff->refresh();
        $this->assertFalse($staff->must_change_password);
        $this->assertNull($staff->temporary_password_expires_at);
        $this->assertTrue(Hash::check('Permanent!Password2345', $staff->password));
    }

    public function test_admin_can_issue_a_replacement_temporary_password_without_changing_roles_or_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $staff = User::factory()->create(['user_type' => UserType::Faculty]);
        $staff->assignRole(['research-facilitator', 'panel-member']);

        $component = Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->call('confirmPasswordReset', $staff->id)
            ->call('issueTemporaryPassword')
            ->assertHasNoErrors();

        $temporaryPassword = $component->get('issuedTemporaryPassword');
        $staff->refresh();

        $this->assertTrue(Hash::check($temporaryPassword, $staff->password));
        $this->assertTrue($staff->must_change_password);
        $this->assertEqualsCanonicalizing(['research-facilitator', 'panel-member'], $staff->getRoleNames()->all());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.temporary-password.issued',
            'auditable_id' => $staff->id,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Livewire\AdminDashboard;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $response = $this->actingAs($student)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
    }

    public function test_admin_can_render_the_user_management_tab(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertOk()
            ->assertDontSee('Updating dashboard')
            ->assertSee('Welcome back, Administrator')
            ->assertSee('User Management')
            ->assertSee('Pending Actions')
            ->assertSee('Security Overview')
            ->assertSee('System Health')
            ->assertSee('Student Registrations')
            ->assertSee('Private Storage')
            ->assertSee('Administrator')
            ->assertSee('Temporary Password');
    }

    public function test_admin_dashboard_does_not_render_sample_records(): void
    {
        $admin = User::factory()->create(['name' => 'Current Administrator']);
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertOk()
            ->assertSee('Current Administrator')
            ->assertSee('No research documents found.')
            ->assertSee('No research proposals found.')
            ->assertSee('No revision records found.')
            ->assertDontSee('AI-Powered Traffic Management System')
            ->assertDontSee('Blockchain-Based Voting System')
            ->assertDontSee('IoT Smart Agriculture')
            ->assertDontSee('Dr. Maria Santos');
    }

    public function test_admin_can_approve_pending_student(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $student = User::factory()->create([
            'status' => AccountStatus::Pending,
            'approved_at' => null,
            'student_id' => 'STU-2026-9999',
            'program' => 'BSCS',
            'year_level' => '3',
        ]);
        $student->assignRole('student-researcher');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('approveStudent', $student->id)
            ->assertSet('successMessage', "Student {$student->name} has been approved.");

        $student->refresh();
        $this->assertEquals(AccountStatus::Active, $student->status);
        $this->assertNotNull($student->approved_at);
    }

    public function test_processed_student_registration_cannot_be_processed_again(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $student = User::factory()->create([
            'status' => AccountStatus::Pending,
            'approved_at' => null,
        ]);
        $student->assignRole('student-researcher');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('approveStudent', $student->id)
            ->call('rejectStudent', $student->id)
            ->assertHasErrors(['account']);

        $this->assertEquals(AccountStatus::Active, $student->refresh()->status);
    }

    public function test_admin_can_reject_pending_student(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $student = User::factory()->create([
            'status' => AccountStatus::Pending,
            'approved_at' => null,
            'student_id' => 'STU-2026-9999',
        ]);
        $student->assignRole('student-researcher');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('rejectStudent', $student->id)
            ->assertSet('successMessage', "Student {$student->name} registration has been rejected.");

        $student->refresh();
        $this->assertEquals(AccountStatus::Rejected, $student->status);
        $this->assertNull($student->approved_at);
    }

    public function test_admin_can_suspend_and_reactivate_another_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $adviser = User::factory()->create([
            'status' => AccountStatus::Active,
            'approved_at' => now(),
        ]);
        $adviser->assignRole('research-adviser');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('suspendUser', $adviser->id)
            ->assertHasNoErrors()
            ->assertSet('successMessage', "Account for {$adviser->name} has been suspended.")
            ->call('activateUser', $adviser->id)
            ->assertHasNoErrors()
            ->assertSet('successMessage', "Account for {$adviser->name} has been activated.");

        $this->assertEquals(AccountStatus::Active, $adviser->refresh()->status);
        $this->assertNotNull($adviser->approved_at);
    }

    public function test_admin_cannot_change_their_own_account_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('suspendUser', $admin->id)
            ->assertForbidden();

        $this->assertEquals(AccountStatus::Active, $admin->refresh()->status);
    }

    public function test_admin_can_create_staff_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSee('College of Engineering, Architecture, and Computing (CEAC)')
            ->set('name', 'Dr. Lourdes Castillo')
            ->set('email', 'l.castillo@ndmu.edu.ph')
            ->set('role', 'college-dean')
            ->set('department', 'Untrusted College Value')
            ->set('password', 'SecurePassword123!')
            ->call('createStaffAccount')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Staff account for Dr. Lourdes Castillo created successfully.');

        $newUser = User::where('email', 'l.castillo@ndmu.edu.ph')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Dr. Lourdes Castillo', $newUser->name);
        $this->assertEquals(config('academic.college.name'), $newUser->department);
        $this->assertEquals(AccountStatus::Active, $newUser->status);
        $this->assertTrue($newUser->hasRole('college-dean'));
    }

    public function test_create_staff_account_validation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('name', '')
            ->set('email', 'not-an-email')
            ->set('role', 'invalid-role')
            ->set('password', 'short')
            ->call('createStaffAccount')
            ->assertHasErrors([
                'name' => 'required',
                'email' => 'email',
                'role' => 'in',
                'password',
            ]);
    }

    public function test_invalid_role_filter_is_discarded(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('selectedRole', 'role-that-does-not-exist')
            ->assertSet('selectedRole', '')
            ->assertOk();
    }
}

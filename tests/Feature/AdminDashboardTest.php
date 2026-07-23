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
            ->assertSee('Admin Dashboard')
            ->assertSee('User Management')
            ->assertSee('Administrator')
            ->assertSee('Temporary Password');
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

    public function test_admin_can_create_staff_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('name', 'Dr. Lourdes Castillo')
            ->set('email', 'l.castillo@ndmu.edu.ph')
            ->set('role', 'college-dean')
            ->set('department', 'Office of the College Dean')
            ->set('password', 'SecurePassword123!')
            ->call('createStaffAccount')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Staff account for Dr. Lourdes Castillo created successfully.');

        $newUser = User::where('email', 'l.castillo@ndmu.edu.ph')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Dr. Lourdes Castillo', $newUser->name);
        $this->assertEquals('Office of the College Dean', $newUser->department);
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
                'password' => 'min',
            ]);
    }
}

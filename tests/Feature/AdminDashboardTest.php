<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Livewire\AdminDashboard;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
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
        $response->assertSee('Log out of NDMU-RMAS?');
        $response->assertSee('data-confirm-logout', false);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $livewireRoots = collect(iterator_to_array($document->getElementsByTagName('*')))
            ->filter(fn (\DOMElement $element): bool => $element->hasAttribute('wire:id'));

        $this->assertCount(1, $livewireRoots);
        $this->assertSame('div', $livewireRoots->first()->tagName);
    }

    public function test_admin_can_render_the_user_management_tab(): void
    {
        $admin = User::factory()->create(['name' => 'Administrator']);
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertOk()
            ->assertDontSee('Updating dashboard')
            ->assertSee('User Management')
            ->assertSee('Security Overview')
            ->assertSee('System Health')
            ->assertSee('Administrator');
    }

    public function test_admin_sidebar_shows_students_awaiting_email_verification(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        User::factory()->count(2)->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Pending,
            'approved_at' => null,
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSeeHtml('aria-label="2 student registrations awaiting email verification"');
    }

    public function test_admin_can_render_and_save_system_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $academicYearId = DB::table('academic_years')->insertGetId([
            'name' => '2026–2027',
            'starts_at' => '2026-08-01',
            'ends_at' => '2027-05-31',
            'is_current' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $academicTermId = DB::table('academic_terms')->insertGetId([
            'academic_year_id' => $academicYearId,
            'name' => 'First Semester',
            'starts_at' => '2026-08-01',
            'ends_at' => '2026-12-20',
            'is_current' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSee('System Settings')
            ->set('settingsSystemName', 'NDMU Research Portal')
            ->set('settingsSupportEmail', 'support@ndmu.edu.ph')
            ->set('settingsStudentRegistrationEnabled', false)
            ->set('settingsEmailNotificationsEnabled', true)
            ->set('settingsMaintenanceNotice', '<b>Scheduled maintenance</b>')
            ->set('settingsAcademicYearId', $academicYearId)
            ->set('settingsAcademicTermId', $academicTermId)
            ->call('saveSystemSettings')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'System settings saved successfully.');

        $settings = SystemSetting::query()->firstOrFail();

        $this->assertSame('NDMU Research Portal', $settings->system_name);
        $this->assertSame('support@ndmu.edu.ph', $settings->support_email);
        $this->assertFalse($settings->student_registration_enabled);
        $this->assertSame('Scheduled maintenance', $settings->maintenance_notice);
        $this->assertSame($admin->id, $settings->updated_by);
        $this->assertDatabaseHas('academic_years', ['id' => $academicYearId, 'is_current' => true]);
        $this->assertDatabaseHas('academic_terms', ['id' => $academicTermId, 'is_current' => true]);
    }

    public function test_admin_can_seed_academic_cycle(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('seedAcademicCycle')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Academic cycle seeded successfully.');

        $this->assertDatabaseHas('academic_years', ['name' => '2026–2027']);
        $this->assertDatabaseHas('academic_terms', ['name' => 'First Semester']);
    }

    public function test_admin_can_create_new_academic_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('openAcademicYearModal')
            ->assertSet('showAcademicYearModal', true)
            ->set('newAcademicYearName', '2027–2028')
            ->set('newAcademicYearStartDate', '2027-08-01')
            ->set('newAcademicYearEndDate', '2028-05-31')
            ->call('createAcademicYear')
            ->assertHasNoErrors()
            ->assertSet('showAcademicYearModal', false)
            ->assertSet('successMessage', 'Academic Year 2027–2028 created successfully.');

        $this->assertDatabaseHas('academic_years', ['name' => '2027–2028']);
    }

    public function test_admin_dashboard_does_not_render_sample_records(): void
    {
        $admin = User::factory()->create(['name' => 'Current Administrator']);
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertOk()
            ->assertSee('Current Administrator')
            ->assertSee('No documents match your current repository view.')
            ->assertSee('No research proposals found.')
            ->assertSee('No revision records found.')
            ->assertDontSee('AI-Powered Traffic Management System')
            ->assertDontSee('Blockchain-Based Voting System')
            ->assertDontSee('IoT Smart Agriculture')
            ->assertDontSee('Dr. Maria Santos');
    }

    public function test_admin_sees_pending_students_as_awaiting_verification_without_approval_controls(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $student = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Pending,
            'approved_at' => null,
            'student_id' => 'STU-2026-9999',
            'program' => 'BSCS',
            'year_level' => '3',
        ]);
        $student->assignRole('student-researcher');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSee($student->name)
            ->assertSee('Waiting for student verification')
            ->assertDontSeeHtml('wire:click="approveStudent(')
            ->assertDontSeeHtml('wire:click="rejectStudent(');

        $this->assertEquals(AccountStatus::Pending, $student->fresh()->status);
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

    public function test_user_management_uses_assign_role_and_disable_action_labels(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $faculty = User::factory()->create([
            'status' => AccountStatus::Active,
            'approved_at' => now(),
        ]);
        $faculty->assignRole('faculty');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSee('Assign Role')
            ->assertSee('Disable');
    }

    public function test_admin_can_filter_and_clear_audit_activity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $otherActor = User::factory()->create();

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'actor_name' => $admin->name,
            'actor_email' => $admin->email,
            'event' => 'workspace.switched',
            'description' => 'Unique workspace audit description.',
            'created_at' => now(),
        ]);
        AuditLog::query()->create([
            'user_id' => $otherActor->id,
            'actor_name' => $otherActor->name,
            'actor_email' => $otherActor->email,
            'subject_name' => 'Target Faculty Member',
            'subject_email' => 'target.faculty@ndmu.edu.ph',
            'event' => 'user.activated',
            'description' => 'Unique account audit description.',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        $test = Livewire::test(AdminDashboard::class)
            ->set('tab', 'audit');

        $logs = $test->viewData('auditLogs');
        $this->assertTrue($logs->contains('description', 'Unique workspace audit description.'));
        $this->assertTrue($logs->contains('description', 'Unique account audit description.'));

        $test->set('auditEvent', 'workspace.switched');
        $logs = $test->viewData('auditLogs');
        $this->assertTrue($logs->contains('description', 'Unique workspace audit description.'));
        $this->assertFalse($logs->contains('description', 'Unique account audit description.'));

        $test->call('clearAuditFilters');
        $this->assertSame('', $test->get('auditEvent'));

        $test->set('auditSearch', 'target.faculty@ndmu.edu.ph');
        $logs = $test->viewData('auditLogs');
        $this->assertFalse($logs->contains('description', 'Unique workspace audit description.'));
        $this->assertTrue($logs->contains('description', 'Unique account audit description.'));
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
            ->set('department', 'Untrusted College Value')
            ->set('password', 'SecurePassword123!')
            ->call('createStaffAccount')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Faculty account for Dr. Lourdes Castillo created. Assign a role when access is required.');

        $newUser = User::where('email', 'l.castillo@ndmu.edu.ph')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Dr. Lourdes Castillo', $newUser->name);
        $this->assertEquals(config('academic.college.name'), $newUser->department);
        $this->assertEquals(AccountStatus::Active, $newUser->status);
        $this->assertTrue($newUser->roles->isEmpty());
        $this->assertSame('faculty', $newUser->user_type->value);
    }

    public function test_create_staff_account_validation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('name', '')
            ->set('email', 'not-an-email')
            ->set('password', 'short')
            ->call('createStaffAccount')
            ->assertHasErrors([
                'name' => 'required',
                'email' => 'email',
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

    public function test_admin_can_create_a_custom_role_from_the_permission_catalog(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSee('Roles & Permissions')
            ->call('createRole')
            ->assertSet('showRoleEditor', true)
            ->set('roleName', 'Ethics Review Coordinator')
            ->set('selectedPermissions', ['research.view-all', 'classes.create', 'reports.view'])
            ->call('saveRole')
            ->assertHasNoErrors()
            ->assertSet('roleName', '')
            ->assertSet('showRoleEditor', false);

        $role = Role::findByName('ethics-review-coordinator');

        $this->assertEqualsCanonicalizing(
            ['research.view-all', 'classes.create', 'reports.view'],
            $role->permissions()->pluck('name')->all(),
        );
    }

    public function test_admin_can_assign_custom_and_multiple_roles_to_another_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $faculty = User::factory()->create();
        $faculty->assignRole('research-facilitator');
        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('openRoleAssignment', $faculty->id)
            ->assertSet('roleAssignmentUserId', $faculty->id)
            ->assertDispatched('role-assignment-opened')
            ->assertSee('Assign Roles')
            ->assertSee($faculty->name)
            ->assertSee('Save Roles')
            ->assertDontSee('Save Assignments')
            ->set('assignedRoles', ['research-facilitator', 'program-coordinator', 'thesis-adviser'])
            ->call('saveUserRoles')
            ->assertHasNoErrors()
            ->assertDispatched('role-assignment-closed');

        $this->assertTrue($faculty->fresh()->hasAllRoles([
            'research-facilitator',
            'program-coordinator',
            'thesis-adviser',
        ]));
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'user.access-updated',
            'subject_name' => $faculty->name,
            'subject_email' => $faculty->email,
        ]);
    }

    public function test_dynamic_role_can_be_the_users_only_access_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $faculty = User::factory()->create();
        $faculty->assignRole('research-facilitator');
        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('openRoleAssignment', $faculty->id)
            ->set('assignedRoles', ['program-coordinator'])
            ->call('saveUserRoles')
            ->assertHasNoErrors();

        $this->assertTrue($faculty->fresh()->hasExactRoles(['program-coordinator']));
    }

    public function test_admin_can_clear_all_roles_from_a_faculty_account(): void
    {
        $admin = User::factory()->create(['user_type' => UserType::Admin]);
        $admin->assignRole('system-administrator');
        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);
        $faculty->assignRole('research-facilitator');
        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('openRoleAssignment', $faculty->id)
            ->set('assignedRoles', [])
            ->call('saveUserRoles')
            ->assertHasNoErrors();

        $this->assertTrue($faculty->fresh()->roles->isEmpty());
    }

    public function test_default_roles_are_editable_but_administrator_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $builtInRole = Role::findByName('research-facilitator');
        $administratorRole = Role::findByName('administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->call('editRole', $builtInRole->id)
            ->assertSet('editingRoleId', $builtInRole->id)
            ->call('deleteRole', $administratorRole->id)
            ->assertHasErrors(['roleName']);

        $this->assertDatabaseHas('roles', ['id' => $builtInRole->id]);
        $this->assertDatabaseHas('roles', ['id' => $administratorRole->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Livewire\AdminDashboard;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\OfficialFormDefinition;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\Dashboard\Queries\GetAdminDashboardData;
use App\Modules\SystemSettings\Services\RateLimitSettings;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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
        $response->assertSee('data-portal-mobile-controls', false);
        $response->assertSee('data-portal-sidebar-toggle', false);

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
            ->set('tab', 'users')
            ->assertOk()
            ->assertDontSee('Updating dashboard')
            ->assertSee('User Management')
            ->assertSeeHtml('aria-label="User accounts table. Scroll horizontally for all columns."')
            ->assertSeeHtml('data-responsive-table-container')
            ->assertSeeHtml('data-portal-content')
            ->assertDontSeeHtml('sticky right-0')
            ->assertSee('Identity & access')
            ->assertSee('Research oversight')
            ->assertSee('System operations')
            ->assertSee('Defense Oversight')
            ->assertSee('Forms & Proposals')
            ->assertDontSee('Schedule New Defense')
            ->assertDontSee('Edit Defense Schedule')
            ->assertSee('Administrator');
    }

    public function test_admin_dashboard_shortcuts_target_existing_content_tabs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->assertSeeHtml("@click=\"activeTab = 'permissions'\"")
            ->assertSeeHtml("@click=\"activeTab = 'research'\"")
            ->assertSeeHtml("@click=\"activeTab = 'repository'\"")
            ->assertSeeHtml("@click=\"activeTab = 'notifications'\"")
            ->assertDontSeeHtml("activeTab = 'roles'")
            ->assertDontSeeHtml("activeTab = 'academic-years'")
            ->assertDontSeeHtml("switchTab('notifications')");
    }

    public function test_admin_oversight_uses_current_defense_schema_and_real_committee_data(): void
    {
        $facilitator = User::factory()->create(['name' => 'Facilitator One']);
        $adviser = User::factory()->create(['name' => 'Adviser One']);
        $panelist = User::factory()->create(['name' => 'Panel Chair']);

        $researchClass = new ResearchClass([
            'facilitator_id' => $facilitator->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Capstone 4A',
            'max_students' => 40,
            'is_active' => true,
        ]);
        $researchClass->setJoinCode('CAPSTONE4A');
        $researchClass->save();

        $group = ResearchClassGroup::query()->create([
            'research_class_id' => $researchClass->id,
            'creation_token' => (string) Str::uuid(),
            'name' => 'Group Alpha',
            'adviser_id' => $adviser->id,
            'created_by' => $facilitator->id,
            'status' => 'active',
        ]);
        $defense = Defense::query()->create([
            'research_class_group_id' => $group->id,
            'defense_type' => 'proposal_defense',
            'status' => 'scheduled',
            'created_by' => $facilitator->id,
        ]);
        $room = DefenseRoom::query()->create([
            'code' => 'CEAC-101',
            'name' => 'CEAC Conference Room',
            'location_notes' => 'First floor',
            'is_active' => true,
        ]);
        $schedule = DefenseSchedule::query()->create([
            'defense_id' => $defense->id,
            'room_id' => $room->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'status' => 'current',
            'scheduled_by' => $facilitator->id,
        ]);
        $defense->update(['current_schedule_id' => $schedule->id]);
        DefensePanelAssignment::query()->create([
            'defense_id' => $defense->id,
            'user_id' => $panelist->id,
            'panel_position' => 'chairperson',
            'assigned_by' => $facilitator->id,
            'assigned_at' => now(),
        ]);

        Cache::forget('admin-dashboard.analytics-data');
        $data = app(GetAdminDashboardData::class)->get();

        $this->assertSame('Group Alpha', $data['defensesList'][0]['title']);
        $this->assertSame('Adviser One', $data['defensesList'][0]['adviser']);
        $this->assertSame('Panel Chair', $data['defensesList'][0]['panelists'][0]['name']);
        $this->assertSame('Chairperson', $data['defensesList'][0]['panelists'][0]['position']);
        $this->assertSame('CEAC Conference Room, First floor', $data['defensesList'][0]['venue']);
    }

    public function test_admin_repository_oversight_returns_real_documents(): void
    {
        $author = User::factory()->create(['name' => 'Student Author']);
        Document::query()->create([
            'user_id' => $author->id,
            'submission_token' => (string) Str::uuid(),
            'original_filename' => 'approved-capstone.pdf',
            'stored_filename' => Str::random(40).'.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'storage_disk' => 'local',
            'storage_path' => 'private/'.Str::uuid().'.pdf',
            'content_sha256' => hash('sha256', 'approved-capstone'),
            'submitted_at' => now(),
            'status' => DocumentStatus::Accepted,
        ]);

        Cache::forget('admin-dashboard.analytics-data');
        $data = app(GetAdminDashboardData::class)->get();

        $this->assertCount(1, $data['repositoryList']);
        $this->assertSame('approved-capstone.pdf', $data['repositoryList'][0]['title']);
        $this->assertSame('Approved', $data['repositoryList'][0]['status']);
        $this->assertSame('Student Author', $data['repositoryList'][0]['author']);
    }

    public function test_admin_can_manage_production_configuration_with_audit_logs(): void
    {
        $this->seed(AcademicStructureSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');
        $this->actingAs($admin);

        $component = Livewire::test(AdminDashboard::class)
            ->set('tab', 'configuration')
            ->assertSee('Institutional Configuration')
            ->set('newDepartmentCode', 'RDC')
            ->set('newDepartmentName', 'Research Development Center')
            ->call('createDepartment')
            ->assertHasNoErrors();

        $departmentId = DB::table('departments')->where('code', 'RDC')->value('id');
        $component
            ->set('newProgramDepartmentId', $departmentId)
            ->set('newProgramCode', 'BSDS')
            ->set('newProgramName', 'Bachelor of Science in Data Science')
            ->set('newProgramDegreeLevel', 'Bachelor')
            ->call('createProgram')
            ->set('newDefenseRoomCode', 'RDC-101')
            ->set('newDefenseRoomName', 'Research Defense Room')
            ->set('newDefenseRoomLocation', 'Research building, first floor')
            ->call('createDefenseRoom')
            ->assertHasNoErrors();

        $definition = OfficialFormDefinition::query()->create([
            'code' => 'RES-099',
            'title' => 'Configuration Test Form',
            'default_category' => 'Test',
            'ownership_scope' => 'research_group',
            'cardinality' => 'single_per_group',
            'template_view' => 'pages.facilitator.forms.res-099',
            'is_active' => true,
            'sort_order' => 999,
        ]);
        $component->call('setOfficialFormActive', $definition->id, false)->assertHasNoErrors();

        $this->assertDatabaseHas('colleges', ['code' => config('academic.college.code')]);
        $this->assertDatabaseHas('programs', ['code' => 'BSDS', 'department_id' => $departmentId]);
        $this->assertDatabaseHas('defense_rooms', ['code' => 'RDC-101', 'is_active' => true]);
        $this->assertDatabaseHas('official_form_definitions', ['id' => $definition->id, 'is_active' => false]);
        foreach (['academic.department.created', 'academic.program.created', 'defense-room.created', 'official-form.definition-status-updated'] as $event) {
            $this->assertDatabaseHas('audit_logs', ['event' => $event, 'user_id' => $admin->id]);
        }
        $this->assertSame(config('academic.college.name'), College::query()->where('code', config('academic.college.code'))->value('name'));
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
            ->set('tab', 'settings')
            ->assertSee('System Settings')
            ->set('settingsSystemName', 'NDMU Research Portal')
            ->set('settingsSupportEmail', 'support@ndmu.edu.ph')
            ->set('settingsStudentRegistrationEnabled', false)
            ->set('settingsEmailNotificationsEnabled', true)
            ->set('settingsTurnstileEnabled', false)
            ->set('settingsDefenseHighTrafficModeEnabled', true)
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
        $this->assertFalse($settings->turnstile_enabled);
        $this->assertTrue($settings->defense_high_traffic_mode_enabled);
        $this->assertSame('Scheduled maintenance', $settings->maintenance_notice);
        $this->assertSame($admin->id, $settings->updated_by);
        $this->assertDatabaseHas('academic_years', ['id' => $academicYearId, 'is_current' => true]);
        $this->assertDatabaseHas('academic_terms', ['id' => $academicTermId, 'is_current' => true]);
    }

    public function test_high_traffic_mode_disables_authenticated_route_limits_but_keeps_guest_limits(): void
    {
        $settings = SystemSetting::query()->firstOrFail();
        $request = Request::create('/defense-test', 'POST');

        $normalLimit = RateLimiter::limiter('defense-actions')($request);

        $this->assertSame(30, $normalLimit->maxAttempts);

        $settings->update(['defense_high_traffic_mode_enabled' => true]);
        Cache::forget(RateLimitSettings::CACHE_KEY);

        foreach (['defense-actions', 'defense-drafts', 'official-form-actions', 'document-reviews'] as $limiterName) {
            $this->assertInstanceOf(Unlimited::class, RateLimiter::limiter($limiterName)($request));
        }

        $authenticationLimit = RateLimiter::limiter('authentication')($request);

        $this->assertNotInstanceOf(Unlimited::class, $authenticationLimit);
        $this->assertSame(10, $authenticationLimit->maxAttempts);

        Route::get('/test/authenticated-numeric-throttle', fn () => response('ok'))
            ->middleware('throttle:1,1,authenticated-high-traffic-test|');
        Route::get('/test/authenticated-class-throttle', fn () => response('ok'))
            ->middleware('throttle:class-creation');
        Route::get('/test/guest-throttle', fn () => response('ok'))
            ->middleware('throttle:1,1,guest-high-traffic-test|');

        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (range(1, 12) as $attempt) {
            $this->get('/test/authenticated-numeric-throttle')->assertOk();
            $this->get('/test/authenticated-class-throttle')->assertOk();
        }

        auth()->logout();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->get('/test/guest-throttle')
            ->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->get('/test/guest-throttle')
            ->assertTooManyRequests();
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

        $component = Livewire::test(AdminDashboard::class)
            ->assertOk()
            ->assertSee('Current Administrator')
            ->assertDontSee('AI-Powered Traffic Management System')
            ->assertDontSee('Blockchain-Based Voting System')
            ->assertDontSee('IoT Smart Agriculture')
            ->assertDontSee('Dr. Maria Santos');

        $component->set('tab', 'repository')
            ->assertSee('No documents match your current repository view.');
        $component->set('tab', 'forms')
            ->assertSee('No research proposals found.');
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
            ->set('tab', 'users')
            ->set('userManagementTab', 'pending-students')
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
            ->set('tab', 'users')
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
            ->set('tab', 'users')
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
                'department' => 'required',
            ]);
    }

    public function test_create_faculty_account_with_department_selection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('name', 'Engr. Jose Montero')
            ->set('email', 'j.montero@ndmu.edu.ph')
            ->set('department', 'CSD')
            ->set('password', 'SecurePassword123!')
            ->call('createStaffAccount')
            ->assertHasNoErrors();

        $newUser = User::where('email', 'j.montero@ndmu.edu.ph')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Engr. Jose Montero', $newUser->name);
        $this->assertEquals('Computer Studies Department', $newUser->department);
        $this->assertEquals(AccountStatus::Active, $newUser->status);
        $this->assertSame('faculty', $newUser->user_type->value);
    }

    public function test_create_faculty_account_as_college_dean(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        Livewire::test(AdminDashboard::class)
            ->set('name', 'Dr. Lourdes Castillo')
            ->set('email', 'dean.castillo@ndmu.edu.ph')
            ->set('isCollegeDean', true)
            ->set('password', 'SecurePassword123!')
            ->call('createStaffAccount')
            ->assertHasNoErrors();

        $newUser = User::where('email', 'dean.castillo@ndmu.edu.ph')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Dr. Lourdes Castillo', $newUser->name);
        $this->assertEquals(config('academic.college.name'), $newUser->department);
        $this->assertEquals(AccountStatus::Active, $newUser->status);
    }

    public function test_create_faculty_account_requires_department_unless_college_dean(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system-administrator');

        $this->actingAs($admin);

        // Attempting to create without department and without isCollegeDean should fail validation
        Livewire::test(AdminDashboard::class)
            ->set('name', 'Prof. Alan Turing')
            ->set('email', 'a.turing@ndmu.edu.ph')
            ->set('password', 'SecurePassword123!')
            ->set('department', '')
            ->set('isCollegeDean', false)
            ->call('createStaffAccount')
            ->assertHasErrors(['department' => 'required']);

        // Marking as College Dean bypasses department requirement
        Livewire::test(AdminDashboard::class)
            ->set('name', 'Prof. Alan Turing')
            ->set('email', 'a.turing@ndmu.edu.ph')
            ->set('password', 'SecurePassword123!')
            ->set('isCollegeDean', true)
            ->call('createStaffAccount')
            ->assertHasNoErrors();
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

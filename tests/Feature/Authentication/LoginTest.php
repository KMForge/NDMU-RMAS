<?php

namespace Tests\Feature\Authentication;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Modules\SystemSettings\Services\TurnstileSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_user_can_log_in_with_a_hashed_password(): void
    {
        $user = User::factory()->create([
            'email' => 'student.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
            'user_type' => UserType::Student,
        ]);
        $user->assignRole('student-researcher');

        $this->postJson(route('login.store'), [
            'email' => 'student.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ])->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('user.roles.0', 'student-researcher')
            ->assertJsonPath('user_type', UserType::Student->value)
            ->assertJsonPath('redirect_url', route('student.dashboard'))
            ->assertJsonPath('user.email', 'student.test@ndmu.edu.ph');

        $this->assertAuthenticatedAs($user);
    }

    public function test_sql_injection_payload_cannot_bypass_login(): void
    {
        User::factory()->create([
            'email' => 'student.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ]);

        $payloads = [
            ["' OR 1=1 --", 'anything'],
            ['student.test@ndmu.edu.ph', "' OR '1'='1"],
            ['student.test@ndmu.edu.ph', "password' OR 1=1 --"],
        ];

        foreach ($payloads as [$email, $password]) {
            $response = $this->postJson(route('login.store'), [
                'email' => $email,
                'password' => $password,
            ]);

            $this->assertSame(422, $response->getStatusCode(), $response->getContent());

            $this->assertGuest();
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
            'status' => AccountStatus::Pending,
            'approved_at' => null,
        ]);
        $user->assignRole('student-researcher');

        $response = $this->postJson(route('login.store'), [
            'email' => 'pending@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());

        $this->assertGuest();
    }

    public function test_verified_student_registered_before_auto_activation_can_log_in(): void
    {
        $student = User::factory()->create([
            'email' => 'legacy.verified.student@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
            'user_type' => UserType::Student,
            'status' => AccountStatus::Pending,
            'approved_at' => null,
        ]);
        $student->assignRole('student');

        $this->postJson(route('login.store'), [
            'email' => $student->email,
            'password' => 'TestOnly!2345',
        ])->assertOk()
            ->assertJsonPath('redirect_url', route('student.dashboard'));

        $student->refresh();

        $this->assertSame(AccountStatus::Active, $student->status);
        $this->assertNotNull($student->approved_at);
        $this->assertAuthenticatedAs($student);
    }

    public function test_active_faculty_without_an_assigned_role_opens_access_pending(): void
    {
        $faculty = User::factory()->create([
            'email' => 'faculty.pending-access@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
            'user_type' => UserType::Faculty,
        ]);

        $this->postJson(route('login.store'), [
            'email' => 'faculty.pending-access@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ])->assertOk()
            ->assertJsonPath('redirect_url', route('access.pending'))
            ->assertJsonPath('user.roles', []);

        $this->assertAuthenticatedAs($faculty);
    }

    public function test_client_supplied_role_cannot_change_the_database_assigned_role(): void
    {
        $user = User::factory()->create([
            'email' => 'admin.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ]);
        $user->assignRole('system-administrator');

        $this->postJson(route('login.store'), [
            'role' => 'student-researcher',
            'email' => 'admin.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ])->assertOk()
            ->assertJsonPath('user.roles.0', 'system-administrator')
            ->assertJsonPath('redirect_url', route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_each_supported_role_redirects_to_its_dashboard(): void
    {
        $destinations = [
            'student' => 'student.dashboard',
            'thesis-adviser' => 'adviser.dashboard',
            'panel-member' => 'panelist.dashboard',
            'research-facilitator' => 'facilitator.dashboard',
            'dean' => 'dean.dashboard',
            'administrator' => 'admin.dashboard',
        ];

        foreach ($destinations as $role => $routeName) {
            $user = User::factory()->create([
                'email' => "{$role}@ndmu.edu.ph",
                'password' => 'TestOnly!2345',
            ]);
            $user->assignRole($role);

            $this->postJson(route('login.store'), [
                'email' => $user->email,
                'password' => 'TestOnly!2345',
            ])->assertOk()
                ->assertJsonPath('user.roles.0', $role)
                ->assertJsonPath('redirect_url', route($routeName));

            $this->postJson(route('logout'))->assertOk();
        }
    }

    public function test_login_form_does_not_contain_a_role_selector(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('name="role"', false);
    }

    public function test_login_form_contains_password_toggle_remember_and_recovery_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-password-input="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee(route('password.request'), false);
    }

    public function test_login_form_renders_recoverable_turnstile_controls_when_configured(): void
    {
        config()->set('services.turnstile.site_key', 'test-site-key');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-ndmu-turnstile-wrapper', false)
            ->assertSee('data-turnstile-retry', false)
            ->assertSee('ndmuTurnstileOnload', false)
            ->assertSee('refresh-expired', false)
            ->assertSee('refresh-timeout', false);
    }

    public function test_login_form_hides_turnstile_when_an_administrator_disables_it(): void
    {
        config()->set('services.turnstile.site_key', 'test-site-key');
        SystemSetting::query()->update(['turnstile_enabled' => false]);
        Cache::forget(TurnstileSettings::CACHE_KEY);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('data-ndmu-turnstile-wrapper', false);
    }

    public function test_remember_me_issues_a_persistent_login_cookie(): void
    {
        $email = 'remembered.student@ndmu.edu.ph';
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'TestOnly!2345',
        ]);
        $user->assignRole('student-researcher');

        $response = $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'TestOnly!2345',
            'remember' => '1',
        ]);

        /** @var SessionGuard $webGuard */
        $webGuard = Auth::guard('web');

        $response
            ->assertRedirect(route('student.dashboard'))
            ->assertCookie($webGuard->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }
}

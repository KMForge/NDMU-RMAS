<?php

namespace Tests\Feature\Authentication;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
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
        ]);
        $user->assignRole('student-researcher');

        $this->postJson(route('login.store'), [
            'email' => 'student.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ])->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('role', 'student-researcher')
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

    public function test_user_without_an_assigned_role_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'student.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ]);

        $this->postJson(route('login.store'), [
            'email' => 'student.test@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
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
            ->assertJsonPath('role', 'system-administrator')
            ->assertJsonPath('redirect_url', route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_each_supported_role_redirects_to_its_dashboard(): void
    {
        $destinations = [
            'student-researcher' => 'student.dashboard',
            'research-adviser' => 'adviser.dashboard',
            'panelist' => 'panelist.dashboard',
            'research-facilitator' => 'facilitator.dashboard',
            'college-dean' => 'dean.dashboard',
            'system-administrator' => 'admin.dashboard',
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
                ->assertJsonPath('role', $role)
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
}

<?php

namespace Tests\Feature\Authentication;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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
            $response = $this->postJson(route('login.store'), compact('email', 'password'));

            $this->assertSame(422, $response->getStatusCode(), $response->getContent());

            $this->assertGuest();
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'pending@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
            'status' => AccountStatus::Pending,
            'approved_at' => null,
        ]);

        $response = $this->postJson(route('login.store'), [
            'email' => 'pending@ndmu.edu.ph',
            'password' => 'TestOnly!2345',
        ]);

        $this->assertSame(422, $response->getStatusCode(), $response->getContent());

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_a_role_dashboard(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('student-researcher');

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_pending_account_is_forbidden_even_with_role_and_permission(): void
    {
        $user = User::factory()->pendingApproval()->create();
        $user->assignRole('student-researcher');

        $this->actingAs($user)->get(route('student.dashboard'))->assertForbidden();
    }

    public function test_active_approved_student_can_open_student_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student-researcher');

        $this->actingAs($user)->get(route('student.dashboard'))->assertOk();
    }
}

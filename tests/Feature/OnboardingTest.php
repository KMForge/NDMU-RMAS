<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_first_name_ignores_an_academic_honorific(): void
    {
        $user = User::factory()->make(['name' => 'Engr. Jose Montero']);

        $this->assertSame('Jose', $user->displayFirstName());
    }

    public function test_user_can_complete_an_authorized_workspace_introduction_once(): void
    {
        $permission = Permission::findOrCreate('dashboards.facilitator.view');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->actingAs($user)
            ->postJson(route('onboarding.complete', 'facilitator'), ['status' => 'completed'])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('onboarding.complete', 'facilitator'), ['status' => 'skipped'])
            ->assertOk();

        $this->assertDatabaseCount('user_onboarding_completions', 1);
        $this->assertDatabaseHas('user_onboarding_completions', [
            'user_id' => $user->id,
            'workspace' => 'facilitator',
            'status' => 'skipped',
        ]);
    }

    public function test_user_cannot_complete_an_unauthorized_workspace_introduction(): void
    {
        $permission = Permission::findOrCreate('dashboards.facilitator.view');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->actingAs($user)
            ->postJson(route('onboarding.complete', 'admin'), ['status' => 'completed'])
            ->assertForbidden();
    }
}

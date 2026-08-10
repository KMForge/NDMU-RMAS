<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RebuildFeatureBackendDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_feature_backend_routes_return_a_rebuild_baseline_response(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student-researcher');

        $this->actingAs($user)
            ->postJson(route('student.consultations.store'))
            ->assertGone()
            ->assertJsonPath('message', 'This backend feature is disabled in the rebuild baseline.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_renders_successfully_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('NDMU Research Management System');
        $response->assertSee('Log in');
        $response->assertSee('Register Account');
    }

    public function test_welcome_page_renders_dashboard_link_for_authenticated_users(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Go to Dashboard');
    }
}

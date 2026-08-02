<?php

namespace Tests\Feature\Authentication;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'student@ndmu.edu.ph']);

        $this->post(route('password.email'), [
            'email' => ' STUDENT@NDMU.EDU.PH ',
        ])->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_request_does_not_reveal_unknown_accounts(): void
    {
        Notification::fake();

        $response = $this->postJson(route('password.email'), [
            'email' => 'missing@ndmu.edu.ph',
        ]);

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'If an account exists for that email address, a password reset link has been sent.',
            );

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $email = 'student@ndmu.edu.ph';
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'OldPassword!2345',
        ]);
        $oldRememberToken = $user->getRememberToken();

        /** @var PasswordBroker $passwordBroker */
        $passwordBroker = Password::broker();
        $token = $passwordBroker->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $email,
            'password' => 'NewPassword!2345',
            'password_confirmation' => 'NewPassword!2345',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword!2345', $user->getAuthPassword()));
        $this->assertNotSame($oldRememberToken, $user->getRememberToken());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $email]);
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $email = 'student@ndmu.edu.ph';
        User::factory()->create(['email' => $email]);

        $this->postJson(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $email,
            'password' => 'NewPassword!2345',
            'password_confirmation' => 'NewPassword!2345',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}

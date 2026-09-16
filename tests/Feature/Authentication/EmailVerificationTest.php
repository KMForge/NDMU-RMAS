<?php

namespace Tests\Feature\Authentication;

use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\SendNDMUEmailVerification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('student');
        SystemSetting::query()->updateOrCreate(
            ['key' => 'student_registration_enabled'],
            ['value' => true]
        );
    }

    public function test_student_registration_dispatches_ndmu_email_verification_notification(): void
    {
        Notification::fake();

        $program = config('academic.programs.6.label');

        $response = $this->post('/register', [
            'student_id' => '2026-00001',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@ndmu.edu.ph',
            'program' => $program,
            'year_level' => 4,
            'password' => 'SecurePass123!@#',
            'password_confirmation' => 'SecurePass123!@#',
        ]);

        $response->assertRedirect('/login');

        $user = User::query()->where('email', 'juan.delacruz@ndmu.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo(
            $user,
            SendNDMUEmailVerification::class,
            function (SendNDMUEmailVerification $notification, array $channels) use ($user) {
                $mail = $notification->toMail($user);

                return $mail->subject === 'Verify Your NDMU-RMAS Student Account (@ndmu.edu.ph)'
                    && str_contains($mail->actionText, 'Verify Institutional Email');
            }
        );
    }

    public function test_student_registration_stores_structured_name_fields(): void
    {
        Notification::fake();
        $program = config('academic.programs.6.label');

        $response = $this->post('/register', [
            'student_id' => '2026-00002',
            'first_name' => 'Juan',
            'middle_name' => 'Silang',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'email' => 'juan.jr@ndmu.edu.ph',
            'program' => $program,
            'year_level' => 4,
            'password' => 'SecurePass123!@#',
            'password_confirmation' => 'SecurePass123!@#',
        ]);

        $response->assertRedirect('/login');

        $user = User::query()->where('email', 'juan.jr@ndmu.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertSame('Juan', $user->first_name);
        $this->assertSame('Silang', $user->middle_name);
        $this->assertSame('Dela Cruz', $user->last_name);
        $this->assertSame('Jr.', $user->suffix);
        $this->assertSame('Juan Silang Dela Cruz Jr.', $user->name);
        $this->assertSame('Juan', $user->displayFirstName());
    }

    public function test_student_can_verify_email_using_signed_url(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email' => 'maria.santos@ndmu.edu.ph',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/login');
    }
}

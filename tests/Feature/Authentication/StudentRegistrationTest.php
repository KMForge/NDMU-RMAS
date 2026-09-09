<?php

namespace Tests\Feature\Authentication;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\SendNDMUEmailVerification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_student_registration_creates_pending_student_with_automatic_role(): void
    {
        Notification::fake();
        $program = config('academic.programs.3.label');

        $this->post(route('register.store'), [
            'student_id' => 'stu-2026-0999',
            'name' => '<b>Juan Dela Cruz</b>',
            'email' => 'JUAN.TEST@NDMU.EDU.PH',
            'program' => $program,
            'year_level' => 4,
            'password' => 'SecureStudent!2345',
            'password_confirmation' => 'SecureStudent!2345',
        ])->assertRedirect(route('login'));

        $student = User::query()->where('email', 'juan.test@ndmu.edu.ph')->firstOrFail();

        $this->assertSame('Juan Dela Cruz', $student->name);
        $this->assertSame('STU-2026-0999', $student->student_id);
        $this->assertSame(UserType::Student, $student->user_type);
        $this->assertSame(AccountStatus::Pending, $student->status);
        $this->assertNull($student->approved_at);
        $this->assertNull($student->email_verified_at);
        $this->assertTrue($student->hasExactRoles(['student']));
        Notification::assertSentTo($student, SendNDMUEmailVerification::class);
    }

    public function test_signed_verification_link_verifies_and_activates_pending_student(): void
    {
        $student = User::factory()->unverified()->pendingApproval()->create([
            'user_type' => UserType::Student,
            'email' => 'verify.student@ndmu.edu.ph',
        ]);
        $student->assignRole('student');

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $student->getKey(),
            'hash' => sha1($student->getEmailForVerification()),
        ]);

        $this->get($url)
            ->assertRedirect(route('login'));

        $student->refresh();

        $this->assertTrue($student->hasVerifiedEmail());
        $this->assertSame(AccountStatus::Active, $student->status);
        $this->assertNotNull($student->approved_at);
    }

    public function test_verification_does_not_reactivate_a_rejected_student(): void
    {
        $student = User::factory()->unverified()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Rejected,
            'approved_at' => null,
            'email' => 'rejected.student@ndmu.edu.ph',
        ]);
        $student->assignRole('student');

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $student->getKey(),
            'hash' => sha1($student->getEmailForVerification()),
        ]);

        $this->get($url)->assertRedirect(route('login'));

        $student->refresh();

        $this->assertTrue($student->hasVerifiedEmail());
        $this->assertSame(AccountStatus::Rejected, $student->status);
        $this->assertNull($student->approved_at);
    }

    public function test_registration_rejects_non_institutional_email_and_unknown_program(): void
    {
        $this->post(route('register.store'), [
            'student_id' => 'STU-2026-0998',
            'name' => 'Invalid Student',
            'email' => 'student@example.com',
            'program' => 'Unknown Program',
            'year_level' => 4,
            'password' => 'SecureStudent!2345',
            'password_confirmation' => 'SecureStudent!2345',
        ])->assertSessionHasErrors(['email', 'program']);
    }
}

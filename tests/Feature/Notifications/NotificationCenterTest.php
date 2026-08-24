<?php

namespace Tests\Feature\Notifications;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ResearchClass;
use App\Models\ResearchClassEnrollment;
use App\Models\User;
use App\Modules\Classes\Actions\RequestToJoinResearchClass;
use App\Modules\Classes\Actions\ReviewResearchClassJoinRequest;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Notifications\AcademicWorkflowNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->string('user_type')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('research_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('facilitator_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('creation_token');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->char('join_code_hash', 64)->unique();
            $table->text('join_code_encrypted');
            $table->unsignedSmallInteger('max_students')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('research_class_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_guest_cannot_access_notification_endpoints(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
        $this->getJson('/notifications/unread-count')->assertUnauthorized();
    }

    public function test_unverified_and_inactive_accounts_are_denied(): void
    {
        $unverified = User::factory()->unverified()->create(['user_type' => UserType::Student]);
        $inactive = User::factory()->create([
            'user_type' => UserType::Student,
            'status' => AccountStatus::Suspended,
        ]);

        $this->actingAs($unverified)->get('/notifications')->assertRedirect(route('verification.notice'));
        $this->actingAs($inactive)->get('/notifications')->assertForbidden();
    }

    public function test_notification_list_count_and_read_actions_are_recipient_scoped(): void
    {
        $userA = $this->activeStudent();
        $userB = $this->activeStudent();
        $notificationA = $this->notify($userA, 'join.approved', 'A notification');
        $notificationB = $this->notify($userB, 'join.approved', 'B notification');

        $this->actingAs($userA)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('A notification')
            ->assertDontSee('B notification');

        $this->actingAs($userA)
            ->getJson('/notifications/unread-count')
            ->assertOk()
            ->assertExactJson(['unread_count' => 1]);

        $this->actingAs($userA)
            ->patch(route('notifications.read', $notificationB->id))
            ->assertNotFound();

        $this->actingAs($userA)
            ->get(route('notifications.open', $notificationB->id))
            ->assertNotFound();

        $this->actingAs($userA)
            ->patch(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertNotNull($notificationA->fresh()->read_at);
        $this->assertNull($notificationB->fresh()->read_at);
    }

    public function test_unknown_destination_falls_back_to_notification_center(): void
    {
        $user = $this->activeStudent();
        $notification = $this->notify($user, 'safe.navigation', 'Safe fallback', 'https://attacker.example');

        $this->actingAs($user)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('notifications.index'));
    }

    public function test_dispatcher_is_idempotent_and_payload_excludes_sensitive_paths(): void
    {
        $recipient = $this->activeStudent();
        $actor = $this->activeStudent();
        $dispatcher = app(WorkflowNotificationDispatcher::class);

        foreach ([1, 2] as $attempt) {
            $dispatcher->send(
                recipient: $recipient,
                eventKey: 'document.reviewed',
                title: 'Document reviewed',
                message: 'A review decision is available.',
                category: 'document',
                routeName: 'student.dashboard',
                routeParameters: ['tab' => 'proposal'],
                sourceType: 'document_review',
                sourceId: 41,
                actor: $actor,
                contextLabel: 'Group Alpha',
                occurrence: 'accepted',
            );
        }

        $this->assertSame(1, $recipient->notifications()->count());
        $payload = json_encode($recipient->notifications()->firstOrFail()->data, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('storage_path', $payload);
        $this->assertStringNotContainsString('signature_path', $payload);
        $this->assertStringNotContainsString('private/', $payload);
    }

    public function test_after_commit_dispatch_does_not_survive_a_rollback(): void
    {
        $recipient = $this->activeStudent();
        $dispatcher = app(WorkflowNotificationDispatcher::class);

        try {
            DB::transaction(function () use ($dispatcher, $recipient): void {
                $dispatcher->send(
                    recipient: $recipient,
                    eventKey: 'consultation.approved',
                    title: 'Consultation approved',
                    message: 'The consultation was approved.',
                    category: 'consultation',
                    routeName: 'student.dashboard',
                    routeParameters: ['tab' => 'consultation'],
                    sourceType: 'consultation_request',
                    sourceId: 9,
                );

                throw new RuntimeException('Force rollback.');
            });
        } catch (RuntimeException) {
            // Expected: the transaction and its after-commit callback are discarded.
        }

        $this->assertSame(0, $recipient->notifications()->count());
    }

    public function test_browser_has_no_notification_creation_endpoint_for_spoofing_recipients(): void
    {
        $this->actingAs($this->activeStudent())
            ->post('/notifications', [
                'recipient_user_id' => User::factory()->create()->id,
                'recipient_role' => 'Administrator',
                'action_url' => 'https://attacker.example',
            ])
            ->assertMethodNotAllowed();
    }

    public function test_join_workflow_notifies_only_contextual_facilitator_then_requesting_student(): void
    {
        $facilitator = User::factory()->create(['user_type' => UserType::Faculty]);
        $unrelatedFaculty = User::factory()->create(['user_type' => UserType::Faculty]);
        $student = $this->activeStudent();
        $class = new ResearchClass([
            'facilitator_id' => $facilitator->id,
            'creation_token' => fake()->uuid(),
            'name' => 'Capstone II - IT4A',
            'max_students' => 50,
            'is_active' => true,
        ]);
        $class->setJoinCode('PHASE23');
        $class->save();

        $request = app(RequestToJoinResearchClass::class)->handle($student, 'PHASE23');

        $this->assertSame(1, $facilitator->notifications()->count());
        $this->assertSame(0, $unrelatedFaculty->notifications()->count());
        $this->assertSame(0, $student->notifications()->count());
        $this->assertSame('class.join-request.submitted', $facilitator->notifications()->first()->data['event_key']);

        app(ReviewResearchClassJoinRequest::class)->approve($facilitator, $class, $request);

        $this->assertSame(1, $student->notifications()->count());
        $this->assertSame('class.join-request.approved', $student->notifications()->first()->data['event_key']);
        $this->assertSame(0, $unrelatedFaculty->notifications()->count());
        $this->assertSame('active', ResearchClassEnrollment::query()->findOrFail($request->id)->status);
    }

    private function activeStudent(): User
    {
        return User::factory()->create(['user_type' => UserType::Student]);
    }

    private function notify(
        User $recipient,
        string $eventKey,
        string $title,
        string $routeName = 'student.dashboard',
    ): DatabaseNotification {
        $recipient->notify(new AcademicWorkflowNotification(
            eventKey: $eventKey,
            title: $title,
            message: 'Recipient-only workflow update.',
            category: 'test',
            logicalKey: hash('sha256', $eventKey.$recipient->id.$title),
            routeName: $routeName,
            routeParameters: ['tab' => 'dashboard'],
            sourceType: 'test_source',
            sourceId: 1,
        ));

        return $recipient->notifications()->firstOrFail();
    }
}

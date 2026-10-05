<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Events\UserLiveStateUpdated;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LiveStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_requires_authentication_for_live_state(): void
    {
        $response = $this->getJson(route('user.live-state'));
        $response->assertStatus(401);
    }

    public function test_returns_live_state_for_authenticated_user(): void
    {
        $user = User::query()->create([
            'name' => 'Jane Scholar',
            'email' => 'jane@ndmu.edu.ph',
            'password' => 'secret',
            'status' => AccountStatus::Active->value,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'user_type' => UserType::Student->value,
        ]);

        $response = $this->actingAs($user)->getJson(route('user.live-state'));

        $response->assertOk()
            ->assertJsonStructure([
                'unread_notifications',
                'recent_notifications',
                'badges' => [
                    'notifications',
                    'forms',
                    'pending',
                ],
            ]);
    }

    public function test_workflow_notification_broadcasts_live_state(): void
    {
        Event::fake([UserLiveStateUpdated::class]);

        $recipient = User::query()->create([
            'name' => 'Recipient User',
            'email' => 'recipient@ndmu.edu.ph',
            'password' => 'secret',
            'status' => AccountStatus::Active->value,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'user_type' => UserType::Student->value,
        ]);

        $dispatcher = app(WorkflowNotificationDispatcher::class);
        $dispatcher->send(
            recipient: $recipient,
            eventKey: 'form.signature_requested',
            title: 'Signature Required',
            message: 'Please review and sign RES-037.',
            category: 'official_forms',
            routeName: 'student.dashboard',
            routeParameters: [],
            sourceType: 'OfficialFormInstance',
            sourceId: 101,
        );

        Event::assertDispatched(UserLiveStateUpdated::class, function (UserLiveStateUpdated $event) use ($recipient) {
            return (int) $event->user->id === (int) $recipient->id
                && ($event->toast['title'] ?? '') === 'Signature Required'
                && ($event->state['unread_notifications'] ?? 0) === 1;
        });
    }

    public function test_marking_all_notifications_read_broadcasts_live_state(): void
    {
        Event::fake([UserLiveStateUpdated::class]);

        $user = User::query()->create([
            'name' => 'Active User',
            'email' => 'active@ndmu.edu.ph',
            'password' => 'secret',
            'status' => AccountStatus::Active->value,
            'approved_at' => now(),
            'email_verified_at' => now(),
            'user_type' => UserType::Student->value,
        ]);

        $response = $this->actingAs($user)->patchJson(route('notifications.read-all'));
        $response->assertOk();

        Event::assertDispatched(UserLiveStateUpdated::class, function (UserLiveStateUpdated $event) use ($user) {
            return (int) $event->user->id === (int) $user->id
                && ($event->state['unread_notifications'] ?? 0) === 0;
        });
    }
}

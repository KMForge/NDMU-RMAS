<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLiveStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, string>|null  $toast
     */
    public function __construct(
        public readonly User $user,
        public readonly array $state,
        public readonly ?array $toast = null,
    ) {}

    /**
     * The channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->user->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'UserLiveStateUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'unread_notifications' => $this->state['unread_notifications'] ?? 0,
            'recent_notifications' => $this->state['recent_notifications'] ?? [],
            'badges' => $this->state['badges'] ?? [],
            'toast' => $this->toast,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

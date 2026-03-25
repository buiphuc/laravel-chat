<?php

namespace PhucBui\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnlineStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $actorType,
        public int $actorId,
        public bool $isOnline
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('chat.online')];
    }

    public function broadcastAs(): string
    {
        return 'user.online_status';
    }

    public function broadcastWith(): array
    {
        return [
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'is_online' => $this->isOnline,
        ];
    }
}

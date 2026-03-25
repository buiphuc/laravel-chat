<?php

namespace PhucBui\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $roomId,
        public string $actorType,
        public int $actorId,
        public bool $isTyping = true
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.room.{$this->roomId}")];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'is_typing' => $this->isTyping,
        ];
    }
}

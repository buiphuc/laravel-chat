<?php

namespace PhucBui\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PhucBui\Chat\Models\ChatRoom;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatRoom $room,
        public string $actorType,
        public int $actorId,
        public string $readAt
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.room.{$this->room->id}")];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room->id,
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'read_at' => $this->readAt,
        ];
    }
}

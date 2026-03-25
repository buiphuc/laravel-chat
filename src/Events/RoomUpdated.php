<?php

namespace PhucBui\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PhucBui\Chat\Models\ChatRoom;

class RoomUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatRoom $room,
        public string $action = 'updated'  // updated | participant_added | participant_removed
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.room.{$this->room->id}")];
    }

    public function broadcastAs(): string
    {
        return 'room.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room->id,
            'action' => $this->action,
            'name' => $this->room->name,
            'max_members' => $this->room->max_members,
        ];
    }
}

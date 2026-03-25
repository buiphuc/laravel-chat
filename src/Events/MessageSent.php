<?php

namespace PhucBui\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public ChatRoom $room
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.room.{$this->room->id}")];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'room_id' => $this->message->room_id,
                'sender_type' => $this->message->sender_type,
                'sender_id' => $this->message->sender_id,
                'type' => $this->message->type,
                'body' => $this->message->body,
                'metadata' => $this->message->metadata,
                'parent_id' => $this->message->parent_id,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
        ];
    }
}

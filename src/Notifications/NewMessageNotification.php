<?php

namespace PhucBui\Chat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ChatMessage $message,
        public ChatRoom $room,
    ) {
    }

    public function via(object $notifiable): array
    {
        return config('chat.notifications.channels', ['database']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'chat_message',
            'room_id' => $this->room->id,
            'room_name' => $this->room->name,
            'message_id' => $this->message->id,
            'message_body' => mb_substr($this->message->body ?? '', 0, 100),
            'message_type' => $this->message->type,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
        ];
    }
}

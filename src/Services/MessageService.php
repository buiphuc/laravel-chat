<?php

namespace PhucBui\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\Repositories\ChatMessageRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoomRepositoryInterface;
use PhucBui\Chat\Contracts\SocketDriverInterface;
use PhucBui\Chat\DTOs\MessageData;
use PhucBui\Chat\Events\MessageRead;
use PhucBui\Chat\Events\MessageSent;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;

class MessageService
{
    public function __construct(
        protected ChatMessageRepositoryInterface $messageRepository,
        protected ChatRoomRepositoryInterface $roomRepository,
        protected ChatParticipantRepositoryInterface $participantRepository,
        protected SocketDriverInterface $driver,
    ) {
    }

    /**
     * Send a message to a room.
     */
    public function send(ChatRoom $room, Model $sender, MessageData $data): ChatMessage
    {
        $message = $this->messageRepository->create([
            'room_id' => $room->id,
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'type' => $data->type,
            'body' => $data->body,
            'metadata' => $data->metadata,
            'parent_id' => $data->parentId,
        ]);

        // Update room's last_message_at
        $this->roomRepository->touchLastMessage($room);

        // Broadcast
        $channel = $this->driver->getChannelName($room);
        $this->driver->broadcast($channel, 'message.sent', [
            'message' => $message->toArray(),
        ]);

        event(new MessageSent($message, $room));

        // Send notifications to offline participants
        if (config('chat.notifications.enabled', false)) {
            $this->notifyOfflineParticipants($room, $sender, $message);
        }

        return $message->fresh(['sender', 'attachments', 'parent']);
    }

    /**
     * Get messages for a room.
     */
    public function getMessages(ChatRoom $room, int $perPage = 50): LengthAwarePaginator
    {
        $perPage = $perPage ?: config('chat.messages.per_page', 50);
        return $this->messageRepository->getByRoom($room->id, $perPage);
    }

    /**
     * Delete a message.
     */
    public function deleteMessage(ChatMessage $message): bool
    {
        return $this->messageRepository->delete($message);
    }

    /**
     * Mark room as read for an actor.
     */
    public function markAsRead(ChatRoom $room, Model $actor): void
    {
        $this->participantRepository->markAsRead($room->id, $actor);

        event(new MessageRead(
            $room,
            $actor->getMorphClass(),
            $actor->getKey(),
            now()->toISOString()
        ));
    }

    /**
     * Search messages.
     */
    public function search(
        string $keyword,
        ?int $roomId = null,
        ?Model $sender = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        return $this->messageRepository->search($keyword, $roomId, $sender, $fromDate, $toDate, $perPage);
    }

    /**
     * Notify participants who are offline.
     *
     * Supports 3 modes via config:
     * 1. Built-in notification (notification_class = null)
     * 2. Custom notification class (notification_class = App\Notifications\YourClass)
     * 3. Disabled (enabled = false) → host listens to MessageSent event
     */
    protected function notifyOfflineParticipants(ChatRoom $room, Model $sender, ChatMessage $message): void
    {
        $participants = $this->participantRepository->getByRoom($room->id);

        // Resolve notification class: custom or built-in
        $notificationClass = config('chat.notifications.notification_class');
        if (!$notificationClass) {
            $notificationClass = \PhucBui\Chat\Notifications\NewMessageNotification::class;
        }

        foreach ($participants as $participant) {
            // Skip sender and muted participants
            if (
                ($participant->actor_type === $sender->getMorphClass() && $participant->actor_id === $sender->getKey())
                || $participant->is_muted
            ) {
                continue;
            }

            $actor = $participant->actor;
            if ($actor && method_exists($actor, 'notify')) {
                $actor->notify(new $notificationClass($message, $room));
            }
        }
    }
}

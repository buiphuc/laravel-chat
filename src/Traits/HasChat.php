<?php

namespace PhucBui\Chat\Traits;

use PhucBui\Chat\Models\ChatParticipant;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatBlockedUser;

trait HasChat
{
    /**
     * Get the display name for chat.
     */
    public function getChatDisplayName(): string
    {
        return app(\PhucBui\Chat\ChatManager::class)->getDisplayName($this);
    }

    /**
     * Get the avatar URL for chat.
     */
    public function getChatAvatar(): ?string
    {
        return app(\PhucBui\Chat\ChatManager::class)->getAvatar($this);
    }

    /**
     * Get all chat rooms this user participates in.
     */
    public function chatRooms()
    {
        return $this->morphToMany(
            ChatRoom::class,
            'actor',
            config('chat.table_names.participants', 'chat_participants'),
            null,
            'room_id'
        );
    }

    /**
     * Get all chat participations.
     */
    public function chatParticipations()
    {
        return $this->morphMany(ChatParticipant::class, 'actor');
    }

    /**
     * Get messages sent by this user.
     */
    public function chatMessages()
    {
        return $this->morphMany(\PhucBui\Chat\Models\ChatMessage::class, 'sender');
    }

    /**
     * Get users blocked by this actor.
     */
    public function chatBlockedUsers()
    {
        return $this->morphMany(ChatBlockedUser::class, 'blocker');
    }

    /**
     * Get block records where this actor is blocked.
     */
    public function chatBlockedBy()
    {
        return $this->morphMany(ChatBlockedUser::class, 'blocked');
    }
}

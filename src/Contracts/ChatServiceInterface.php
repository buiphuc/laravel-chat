<?php

namespace PhucBui\Chat\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\DTOs\MessageData;
use PhucBui\Chat\DTOs\RoomData;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;

interface ChatServiceInterface
{
    /**
     * Create or find an existing direct room between two actors.
     */
    public function findOrCreateDirectRoom(Model $actorA, Model $actorB): ChatRoom;

    /**
     * Create a group room.
     */
    public function createGroupRoom(RoomData $data, Model $creator): ChatRoom;

    /**
     * Send a message to a room.
     */
    public function sendMessage(ChatRoom $room, Model $sender, MessageData $data): ChatMessage;

    /**
     * Get paginated messages for a room.
     */
    public function getMessages(ChatRoom $room, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get rooms for an actor.
     */
    public function getRoomsForActor(Model $actor, int $perPage = 20): LengthAwarePaginator;

    /**
     * Mark room as read for an actor.
     */
    public function markAsRead(ChatRoom $room, Model $actor): void;
}

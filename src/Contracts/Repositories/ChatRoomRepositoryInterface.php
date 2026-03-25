<?php

namespace PhucBui\Chat\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PhucBui\Chat\Models\ChatRoom;

interface ChatRoomRepositoryInterface
{
    public function find(int $id): ?ChatRoom;

    public function findOrFail(int $id): ChatRoom;

    public function create(array $data): ChatRoom;

    public function update(ChatRoom $room, array $data): ChatRoom;

    public function delete(ChatRoom $room): bool;

    /**
     * Find existing direct room between two actors.
     */
    public function findDirectRoom(Model $actorA, Model $actorB): ?ChatRoom;

    /**
     * Get rooms for a specific actor (paginated).
     */
    public function getRoomsByActor(Model $actor, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get all rooms (for super_admin).
     */
    public function getAllRooms(int $perPage = 20): LengthAwarePaginator;

    /**
     * Update last_message_at timestamp.
     */
    public function touchLastMessage(ChatRoom $room): void;
}

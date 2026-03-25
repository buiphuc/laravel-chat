<?php

namespace PhucBui\Chat\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhucBui\Chat\Models\ChatParticipant;

interface ChatParticipantRepositoryInterface
{
    public function create(array $data): ChatParticipant;

    public function update(ChatParticipant $participant, array $data): ChatParticipant;

    public function delete(ChatParticipant $participant): bool;

    /**
     * Find participant in a room.
     */
    public function findInRoom(int $roomId, Model $actor): ?ChatParticipant;

    /**
     * Get all participants of a room.
     */
    public function getByRoom(int $roomId): Collection;

    /**
     * Check if actor is a member of the room.
     */
    public function isMember(int $roomId, Model $actor): bool;

    /**
     * Update last_read_at for a participant.
     */
    public function markAsRead(int $roomId, Model $actor): void;

    /**
     * Count participants in a room.
     */
    public function countInRoom(int $roomId): int;
}

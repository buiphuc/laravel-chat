<?php

namespace PhucBui\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\Repositories\ChatRoomRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoleRepositoryInterface;
use PhucBui\Chat\DTOs\RoomData;
use PhucBui\Chat\Events\RoomUpdated;
use PhucBui\Chat\Models\ChatRoom;

class RoomService
{
    public function __construct(
        protected ChatRoomRepositoryInterface $roomRepository,
        protected ChatParticipantRepositoryInterface $participantRepository,
        protected ChatRoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * Find or create a direct (1v1) room between two actors.
     */
    public function findOrCreateDirectRoom(Model $actorA, Model $actorB): ChatRoom
    {
        $existing = $this->roomRepository->findDirectRoom($actorA, $actorB);

        if ($existing) {
            return $existing;
        }

        $room = $this->roomRepository->create([
            'max_members' => 2,
            'created_by_type' => $actorA->getMorphClass(),
            'created_by_id' => $actorA->getKey(),
        ]);

        $ownerRole = $this->roleRepository->findByName('owner');
        $memberRole = $this->roleRepository->findByName('member') ?? $this->roleRepository->getDefault();

        $this->addParticipant($room, $actorA, $ownerRole?->id);
        $this->addParticipant($room, $actorB, $memberRole?->id);

        return $room->fresh(['participants.actor']);
    }

    /**
     * Create a group room.
     */
    public function createGroupRoom(RoomData $data, Model $creator): ChatRoom
    {
        $room = $this->roomRepository->create([
            'name' => $data->name,
            'max_members' => $data->maxMembers,
            'created_by_type' => $creator->getMorphClass(),
            'created_by_id' => $creator->getKey(),
            'metadata' => $data->metadata,
        ]);

        $ownerRole = $this->roleRepository->findByName('owner');
        $this->addParticipant($room, $creator, $ownerRole?->id);

        // Add other participants
        if (!empty($data->participantIds) && $data->participantType) {
            $memberRole = $this->roleRepository->findByName('member') ?? $this->roleRepository->getDefault();
            $modelClass = $data->participantType;

            foreach ($data->participantIds as $participantId) {
                $actor = $modelClass::find($participantId);
                if ($actor) {
                    $this->addParticipant($room, $actor, $memberRole?->id);
                }
            }
        }

        return $room->fresh(['participants.actor']);
    }

    /**
     * Update a room.
     */
    public function updateRoom(ChatRoom $room, array $data): ChatRoom
    {
        $room = $this->roomRepository->update($room, $data);
        event(new RoomUpdated($room, 'updated'));
        return $room;
    }

    /**
     * Delete a room.
     */
    public function deleteRoom(ChatRoom $room): bool
    {
        return $this->roomRepository->delete($room);
    }

    /**
     * Add a participant to a room.
     */
    public function addParticipant(ChatRoom $room, Model $actor, ?int $roleId = null): void
    {
        if ($this->participantRepository->isMember($room->id, $actor)) {
            return;
        }

        // Check max_members limit
        if ($room->max_members !== null) {
            $currentCount = $this->participantRepository->countInRoom($room->id);
            if ($currentCount >= $room->max_members) {
                throw new \RuntimeException("Room has reached maximum number of members ({$room->max_members}).");
            }
        }

        if (!$roleId) {
            $defaultRole = $this->roleRepository->findByName('member') ?? $this->roleRepository->getDefault();
            $roleId = $defaultRole?->id;
        }

        $this->participantRepository->create([
            'room_id' => $room->id,
            'actor_type' => $actor->getMorphClass(),
            'actor_id' => $actor->getKey(),
            'role_id' => $roleId,
            'joined_at' => now(),
        ]);

        event(new RoomUpdated($room, 'participant_added'));
    }

    /**
     * Remove a participant from a room.
     */
    public function removeParticipant(ChatRoom $room, Model $actor): void
    {
        $participant = $this->participantRepository->findInRoom($room->id, $actor);

        if ($participant) {
            $this->participantRepository->delete($participant);
            event(new RoomUpdated($room, 'participant_removed'));
        }
    }

    /**
     * Get rooms for an actor.
     */
    public function getRoomsForActor(Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        return $this->roomRepository->getRoomsByActor($actor, $perPage);
    }

    /**
     * Get all rooms (super_admin).
     */
    public function getAllRooms(int $perPage = 20): LengthAwarePaginator
    {
        return $this->roomRepository->getAllRooms($perPage);
    }
}

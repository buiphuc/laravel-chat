<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\Repositories\ChatRoomRepositoryInterface;
use PhucBui\Chat\Models\ChatRoom;

class ChatRoomRepository extends BaseRepository implements ChatRoomRepositoryInterface
{
    public function __construct(ChatRoom $model)
    {
        parent::__construct($model);
    }

    public function find(int $id): ?ChatRoom
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): ChatRoom
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): ChatRoom
    {
        return $this->model->create($data);
    }

    public function update(ChatRoom|Model $room, array $data): ChatRoom
    {
        $room->update($data);
        return $room->fresh();
    }

    public function delete(ChatRoom|Model $room): bool
    {
        return $room->delete();
    }

    public function findDirectRoom(Model $actorA, Model $actorB): ?ChatRoom
    {
        return $this->model
            ->where('max_members', 2)
            ->whereHas('participants', function ($query) use ($actorA) {
                $query->where('actor_type', $actorA->getMorphClass())
                      ->where('actor_id', $actorA->getKey());
            })
            ->whereHas('participants', function ($query) use ($actorB) {
                $query->where('actor_type', $actorB->getMorphClass())
                      ->where('actor_id', $actorB->getKey());
            })
            ->first();
    }

    public function getRoomsByActor(Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->whereHas('participants', function ($query) use ($actor) {
                $query->where('actor_type', $actor->getMorphClass())
                      ->where('actor_id', $actor->getKey());
            })
            ->with(['participants.actor', 'latestMessage.sender'])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function getAllRooms(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->with(['participants.actor', 'latestMessage.sender'])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function touchLastMessage(ChatRoom $room): void
    {
        $room->update(['last_message_at' => now()]);
    }
}

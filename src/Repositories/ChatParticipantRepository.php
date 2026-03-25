<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Models\ChatParticipant;

class ChatParticipantRepository extends BaseRepository implements ChatParticipantRepositoryInterface
{
    public function __construct(ChatParticipant $model)
    {
        parent::__construct($model);
    }

    public function create(array $data): ChatParticipant
    {
        return $this->model->create($data);
    }

    public function update(ChatParticipant|Model $participant, array $data): ChatParticipant
    {
        $participant->update($data);
        return $participant->fresh();
    }

    public function delete(ChatParticipant|Model $participant): bool
    {
        return $participant->delete();
    }

    public function findInRoom(int $roomId, Model $actor): ?ChatParticipant
    {
        return $this->model
            ->where('room_id', $roomId)
            ->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey())
            ->first();
    }

    public function getByRoom(int $roomId): Collection
    {
        return $this->model
            ->where('room_id', $roomId)
            ->with(['actor', 'role'])
            ->get();
    }

    public function isMember(int $roomId, Model $actor): bool
    {
        return $this->model
            ->where('room_id', $roomId)
            ->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey())
            ->exists();
    }

    public function markAsRead(int $roomId, Model $actor): void
    {
        $this->model
            ->where('room_id', $roomId)
            ->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey())
            ->update(['last_read_at' => now()]);
    }

    public function countInRoom(int $roomId): int
    {
        return $this->model->where('room_id', $roomId)->count();
    }
}

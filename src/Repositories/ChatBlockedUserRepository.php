<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhucBui\Chat\Contracts\Repositories\ChatBlockedUserRepositoryInterface;
use PhucBui\Chat\Models\ChatBlockedUser;

class ChatBlockedUserRepository extends BaseRepository implements ChatBlockedUserRepositoryInterface
{
    public function __construct(ChatBlockedUser $model)
    {
        parent::__construct($model);
    }

    public function create(array $data): ChatBlockedUser
    {
        return $this->model->create($data);
    }

    public function delete(ChatBlockedUser|Model $blocked): bool
    {
        return $blocked->delete();
    }

    public function isBlocked(Model $blocker, Model $blocked): bool
    {
        return $this->model
            ->where('blocker_type', $blocker->getMorphClass())
            ->where('blocker_id', $blocker->getKey())
            ->where('blocked_type', $blocked->getMorphClass())
            ->where('blocked_id', $blocked->getKey())
            ->exists();
    }

    public function findBlock(Model $blocker, Model $blocked): ?ChatBlockedUser
    {
        return $this->model
            ->where('blocker_type', $blocker->getMorphClass())
            ->where('blocker_id', $blocker->getKey())
            ->where('blocked_type', $blocked->getMorphClass())
            ->where('blocked_id', $blocked->getKey())
            ->first();
    }

    public function getBlockedByActor(Model $actor): Collection
    {
        return $this->model
            ->where('blocker_type', $actor->getMorphClass())
            ->where('blocker_id', $actor->getKey())
            ->with('blocked')
            ->get();
    }
}

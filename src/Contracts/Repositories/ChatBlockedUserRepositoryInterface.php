<?php

namespace PhucBui\Chat\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhucBui\Chat\Models\ChatBlockedUser;

interface ChatBlockedUserRepositoryInterface
{
    public function create(array $data): ChatBlockedUser;

    public function delete(ChatBlockedUser $blocked): bool;

    /**
     * Check if blocker has blocked the target.
     */
    public function isBlocked(Model $blocker, Model $blocked): bool;

    /**
     * Find block record.
     */
    public function findBlock(Model $blocker, Model $blocked): ?ChatBlockedUser;

    /**
     * Get all users blocked by an actor.
     */
    public function getBlockedByActor(Model $actor): Collection;
}

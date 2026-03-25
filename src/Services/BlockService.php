<?php

namespace PhucBui\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhucBui\Chat\Contracts\Repositories\ChatBlockedUserRepositoryInterface;
use PhucBui\Chat\Models\ChatBlockedUser;

class BlockService
{
    public function __construct(
        protected ChatBlockedUserRepositoryInterface $blockedUserRepository,
    ) {
    }

    /**
     * Block a user.
     */
    public function block(Model $blocker, Model $blocked, ?string $reason = null): ChatBlockedUser
    {
        if ($this->isBlocked($blocker, $blocked)) {
            throw new \RuntimeException('User is already blocked.');
        }

        return $this->blockedUserRepository->create([
            'blocker_type' => $blocker->getMorphClass(),
            'blocker_id' => $blocker->getKey(),
            'blocked_type' => $blocked->getMorphClass(),
            'blocked_id' => $blocked->getKey(),
            'reason' => $reason,
        ]);
    }

    /**
     * Unblock a user.
     */
    public function unblock(Model $blocker, Model $blocked): bool
    {
        $block = $this->blockedUserRepository->findBlock($blocker, $blocked);

        if (!$block) {
            throw new \RuntimeException('User is not blocked.');
        }

        return $this->blockedUserRepository->delete($block);
    }

    /**
     * Check if one user has blocked another.
     */
    public function isBlocked(Model $blocker, Model $blocked): bool
    {
        return $this->blockedUserRepository->isBlocked($blocker, $blocked);
    }

    /**
     * Check if either user has blocked the other (bidirectional).
     */
    public function isBlockedBidirectional(Model $actorA, Model $actorB): bool
    {
        return $this->blockedUserRepository->isBlocked($actorA, $actorB)
            || $this->blockedUserRepository->isBlocked($actorB, $actorA);
    }

    /**
     * Get all users blocked by an actor.
     */
    public function getBlockedUsers(Model $actor): Collection
    {
        return $this->blockedUserRepository->getBlockedByActor($actor);
    }
}

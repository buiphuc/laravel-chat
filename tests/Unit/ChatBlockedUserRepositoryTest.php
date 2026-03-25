<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Repositories\ChatBlockedUserRepository;
use PhucBui\Chat\Models\ChatBlockedUser;

class ChatBlockedUserRepositoryTest extends TestCase
{
    protected ChatBlockedUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ChatBlockedUserRepository(new ChatBlockedUser());
    }

    public function test_is_blocked_and_find_block()
    {
        $blocker = $this->createActorUser('client');
        $blocked = $this->createActorUser('client');

        $this->assertFalse($this->repository->isBlocked($blocker, $blocked));

        $this->repository->create([
            'blocker_type' => $blocker->getMorphClass(),
            'blocker_id' => $blocker->id,
            'blocked_type' => $blocked->getMorphClass(),
            'blocked_id' => $blocked->id,
            'reason' => 'Spam',
        ]);

        $this->assertTrue($this->repository->isBlocked($blocker, $blocked));
        $this->assertFalse($this->repository->isBlocked($blocked, $blocker)); // Directional check

        $blockRecord = $this->repository->findBlock($blocker, $blocked);
        $this->assertNotNull($blockRecord);
        $this->assertEquals('Spam', $blockRecord->reason);
    }

    public function test_get_blocked_by_actor()
    {
        $blocker = $this->createActorUser('client');
        $blocked1 = $this->createActorUser('client');
        $blocked2 = $this->createActorUser('client');

        $this->repository->create([
            'blocker_type' => $blocker->getMorphClass(),
            'blocker_id' => $blocker->id,
            'blocked_type' => $blocked1->getMorphClass(),
            'blocked_id' => $blocked1->id,
            'reason' => 'Spam',
        ]);

        $this->repository->create([
            'blocker_type' => $blocker->getMorphClass(),
            'blocker_id' => $blocker->id,
            'blocked_type' => $blocked2->getMorphClass(),
            'blocked_id' => $blocked2->id,
            'reason' => 'Abuse',
        ]);

        $blockedUsers = $this->repository->getBlockedByActor($blocker);
        
        $this->assertCount(2, $blockedUsers);
    }
}

<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Services\BlockService;
use PhucBui\Chat\Tests\TestCase;

class BlockServiceTest extends TestCase
{
    protected BlockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BlockService::class);
    }

    public function test_block_unblock()
    {
        $blocker = $this->createActorUser('client');
        $blocked = $this->createActorUser('client');

        $blockRecord = $this->service->block($blocker, $blocked, 'Spam');

        $this->assertNotNull($blockRecord);
        $this->assertEquals('Spam', $blockRecord->reason);
        $this->assertTrue($this->service->isBlocked($blocker, $blocked));
        $this->assertFalse($this->service->isBlocked($blocked, $blocker));
        $this->assertTrue($this->service->isBlockedBidirectional($blocker, $blocked));

        // Duplicate block throws exception
        $this->expectException(\RuntimeException::class);
        $this->service->block($blocker, $blocked, 'Spam again');

        // Unblock
        $this->service->unblock($blocker, $blocked);
        $this->assertFalse($this->service->isBlocked($blocker, $blocked));
    }

    public function test_unblock_not_blocked()
    {
        $blocker = $this->createActorUser('client');
        $blocked = $this->createActorUser('client');

        $this->expectException(\RuntimeException::class);
        $this->service->unblock($blocker, $blocked);
    }

    public function test_get_blocked_users()
    {
        $blocker = $this->createActorUser('client');
        
        $this->service->block($blocker, $this->createActorUser('client'));
        $this->service->block($blocker, $this->createActorUser('client'));

        $blockedList = $this->service->getBlockedUsers($blocker);

        $this->assertCount(2, $blockedList);
    }
}

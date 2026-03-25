<?php

namespace PhucBui\Chat\Tests\Unit;

use Illuminate\Support\Facades\Event;
use PhucBui\Chat\DTOs\RoomData;
use PhucBui\Chat\Events\RoomUpdated;
use PhucBui\Chat\Models\ChatRole;
use PhucBui\Chat\Services\RoomService;
use PhucBui\Chat\Tests\TestCase;

class RoomServiceTest extends TestCase
{
    protected RoomService $service;

    protected function setUp(): void
    {
        parent::setUp();
        ChatRole::create(['name' => 'owner', 'display_name' => 'Owner', 'permissions' => []]);
        ChatRole::create(['name' => 'member', 'display_name' => 'Member', 'permissions' => []]);
        $this->service = app(RoomService::class);
    }

    public function test_find_or_create_direct_room()
    {
        $actorA = $this->createActorUser('client');
        $actorB = $this->createActorUser('client');

        $room1 = $this->service->findOrCreateDirectRoom($actorA, $actorB);

        $this->assertNotNull($room1);
        $this->assertEquals(2, $room1->max_members);
        $this->assertEquals(2, $room1->participants()->count());

        // Call again should return the same room
        $room2 = $this->service->findOrCreateDirectRoom($actorA, $actorB);
        $this->assertEquals($room1->id, $room2->id);
    }

    public function test_create_group_room()
    {
        $creator = $this->createActorUser('admin');
        $member1 = $this->createActorUser('client');
        $member2 = $this->createActorUser('client');

        $data = RoomData::fromArray([
            'name' => 'Support Group',
            'max_members' => 10,
            'participant_ids' => [$member1->id, $member2->id],
            'participant_type' => $member1->getMorphClass(),
        ]);

        $room = $this->service->createGroupRoom($data, $creator);

        $this->assertEquals('Support Group', $room->name);
        $this->assertEquals(10, $room->max_members);
        $this->assertEquals(3, $room->participants()->count()); // creator + 2 members
    }

    public function test_add_and_remove_participant()
    {
        Event::fake();

        $actorA = $this->createActorUser('client');
        $actorB = $this->createActorUser('client');

        $room = $this->service->findOrCreateDirectRoom($actorA, $actorB);
        
        // Remove B
        $this->service->removeParticipant($room, $actorB);
        Event::assertDispatched(RoomUpdated::class, function ($e) {
            return $e->action === 'participant_removed';
        });

        $this->assertEquals(1, $room->participants()->count());

        // Re-add B
        $this->service->addParticipant($room, $actorB);
        Event::assertDispatched(RoomUpdated::class, function ($e) {
            return $e->action === 'participant_added';
        });

        $this->assertEquals(2, $room->participants()->count());

        // Max members trigger test
        $actorC = $this->createActorUser('client');
        $this->expectException(\RuntimeException::class);
        $this->service->addParticipant($room, $actorC);
    }
}

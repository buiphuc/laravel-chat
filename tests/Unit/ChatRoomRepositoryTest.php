<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Repositories\ChatRoomRepository;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatRole;

class ChatRoomRepositoryTest extends TestCase
{
    protected ChatRoomRepository $repository;
    protected ChatRole $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ChatRoomRepository(new ChatRoom());
        $this->role = ChatRole::create(['name' => 'member', 'display_name' => 'Member', 'permissions' => []]);
    }

    public function test_can_create_room(): void
    {
        $user = $this->createActorUser('client');
        $room = $this->repository->create([
            'name' => 'Test Room',
            'max_members' => 5,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        $this->assertEquals('Test Room', $room->name);
        $this->assertEquals(5, $room->max_members);
        $this->assertEquals($user->getMorphClass(), $room->created_by_type);
    }

    public function test_find_direct_room(): void
    {
        $user1 = $this->createActorUser('client');
        $user2 = $this->createActorUser('client');
        $user3 = $this->createActorUser('admin');

        $room = $this->repository->create([
            'max_members' => 2,
            'created_by_type' => $user1->getMorphClass(),
            'created_by_id' => $user1->id,
        ]);

        $room->participants()->create([
            'actor_type' => $user1->getMorphClass(),
            'actor_id' => $user1->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $room->participants()->create([
            'actor_type' => $user2->getMorphClass(),
            'actor_id' => $user2->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $foundRoom = $this->repository->findDirectRoom($user1, $user2);

        $this->assertNotNull($foundRoom);
        $this->assertEquals($room->id, $foundRoom->id);

        // Test with different pairs
        $this->assertNull($this->repository->findDirectRoom($user1, $user3));
    }

    public function test_get_rooms_by_actor(): void
    {
        $user = $this->createActorUser('client');
        
        $room1 = $this->repository->create([
            'name' => 'Room 1',
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
            'last_message_at' => now()->subDay(),
        ]);
        
        $room1->participants()->create([
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $room2 = $this->repository->create([
            'name' => 'Room 2',
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
            'last_message_at' => now(),
        ]);

        $room2->participants()->create([
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $paginator = $this->repository->getRoomsByActor($user);

        $this->assertEquals(2, $paginator->total());
        // Ordered by last_message_at desc
        $this->assertEquals($room2->id, $paginator->items()[0]->id);
        $this->assertEquals($room1->id, $paginator->items()[1]->id);
    }
}

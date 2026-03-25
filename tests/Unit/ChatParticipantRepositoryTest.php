<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Repositories\ChatParticipantRepository;
use PhucBui\Chat\Models\ChatParticipant;
use PhucBui\Chat\Models\ChatRole;
use PhucBui\Chat\Models\ChatRoom;

class ChatParticipantRepositoryTest extends TestCase
{
    protected ChatParticipantRepository $repository;
    protected ChatRole $role;
    protected ChatRoom $room;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ChatParticipantRepository(new ChatParticipant());
        $this->role = ChatRole::create(['name' => 'member', 'display_name' => 'Member', 'permissions' => []]);
        $this->user = $this->createActorUser('client');
        $this->room = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $this->user->getMorphClass(),
            'created_by_id' => $this->user->id,
        ]);
    }

    public function test_find_in_room()
    {
        $this->repository->create([
            'room_id' => $this->room->id,
            'actor_type' => $this->user->getMorphClass(),
            'actor_id' => $this->user->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $participant = $this->repository->findInRoom($this->room->id, $this->user);
        
        $this->assertNotNull($participant);
        $this->assertEquals($this->user->id, $participant->actor_id);
    }

    public function test_is_member()
    {
        $this->assertFalse($this->repository->isMember($this->room->id, $this->user));

        $this->repository->create([
            'room_id' => $this->room->id,
            'actor_type' => $this->user->getMorphClass(),
            'actor_id' => $this->user->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $this->assertTrue($this->repository->isMember($this->room->id, $this->user));
    }

    public function test_get_by_room_and_count()
    {
        $this->repository->create([
            'room_id' => $this->room->id,
            'actor_type' => $this->user->getMorphClass(),
            'actor_id' => $this->user->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $user2 = $this->createActorUser('client');
        $this->repository->create([
            'room_id' => $this->room->id,
            'actor_type' => $user2->getMorphClass(),
            'actor_id' => $user2->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $participants = $this->repository->getByRoom($this->room->id);
        $this->assertCount(2, $participants);

        $count = $this->repository->countInRoom($this->room->id);
        $this->assertEquals(2, $count);
    }

    public function test_mark_as_read()
    {
        $participant = $this->repository->create([
            'room_id' => $this->room->id,
            'actor_type' => $this->user->getMorphClass(),
            'actor_id' => $this->user->id,
            'role_id' => $this->role->id,
            'joined_at' => now(),
        ]);

        $this->assertNull($participant->last_read_at);

        $this->repository->markAsRead($this->room->id, $this->user);

        $updatedParticipant = $this->repository->findInRoom($this->room->id, $this->user);
        $this->assertNotNull($updatedParticipant->last_read_at);
    }
}

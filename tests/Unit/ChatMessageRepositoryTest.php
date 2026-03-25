<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Repositories\ChatMessageRepository;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Tests\TestUser;

class ChatMessageRepositoryTest extends TestCase
{
    protected ChatMessageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ChatMessageRepository(new ChatMessage());
    }

    public function test_get_by_room()
    {
        $user = $this->createActorUser('client');
        $room = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        $this->repository->create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Message 1',
            'type' => 'text',
            'created_at' => now()->subMinute(),
        ]);
        
        $this->repository->create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Message 2',
            'type' => 'text',
            'created_at' => now(),
        ]);

        $paginator = $this->repository->getByRoom($room->id);
        
        $this->assertEquals(2, $paginator->total());
        // Ordered desc by default
        $this->assertEquals('Message 2', $paginator->items()[0]->body);
        $this->assertEquals('Message 1', $paginator->items()[1]->body);
    }

    public function test_search_messages()
    {
        $user = $this->createActorUser('client');
        $room = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        $this->repository->create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Hello there!',
            'type' => 'text',
        ]);

        $this->repository->create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'How are you?',
            'type' => 'text',
        ]);

        // Keyword "Hello"
        $results = $this->repository->search('Hello', $room->id, null);
        $this->assertEquals(1, $results->total());
        $this->assertEquals('Hello there!', $results->items()[0]->body);

        // Filter by sender
        $resultsSender = $this->repository->search('How', null, $user);
        $this->assertEquals(1, $resultsSender->total());

        // Empty result
        $emptyResults = $this->repository->search('Missing', $room->id);
        $this->assertEquals(0, $emptyResults->total());
    }
}

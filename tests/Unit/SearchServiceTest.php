<?php

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Services\SearchService;
use PhucBui\Chat\Tests\TestCase;

class SearchServiceTest extends TestCase
{
    protected SearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SearchService::class);
    }

    public function test_search_messages()
    {
        config(['chat.messages.search_enabled' => true]);

        $actor = $this->createActorUser('client');
        $room = ChatRoom::create(['max_members' => 2, 'created_by_type' => $actor->getMorphClass(), 'created_by_id' => $actor->id]);
        
        ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $actor->getMorphClass(),
            'sender_id' => $actor->id,
            'body' => 'I love Laravel',
        ]);

        ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $actor->getMorphClass(),
            'sender_id' => $actor->id,
            'body' => 'PHP is great',
        ]);

        $results = $this->service->search('Laravel');
        
        $this->assertEquals(1, $results->total());
        $this->assertEquals('I love Laravel', $results->items()[0]->body);
    }

    public function test_search_disabled()
    {
        config(['chat.messages.search_enabled' => false]);

        $this->expectException(\RuntimeException::class);
        $this->service->search('test');
    }
}

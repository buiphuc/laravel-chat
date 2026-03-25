<?php

namespace PhucBui\Chat\Tests\Unit;

use Illuminate\Support\Facades\Event;
use PhucBui\Chat\Contracts\SocketDriverInterface;
use PhucBui\Chat\DTOs\MessageData;
use PhucBui\Chat\Events\MessageRead;
use PhucBui\Chat\Events\MessageSent;
use PhucBui\Chat\Models\ChatRole;
use PhucBui\Chat\Services\MessageService;
use PhucBui\Chat\Services\RoomService;
use PhucBui\Chat\Tests\TestCase;
use Mockery\MockInterface;

class MessageServiceTest extends TestCase
{
    protected MessageService $messageService;
    protected RoomService $roomService;

    protected function setUp(): void
    {
        parent::setUp();
        ChatRole::create(['name' => 'owner', 'display_name' => 'Owner', 'permissions' => []]);
        ChatRole::create(['name' => 'member', 'display_name' => 'Member', 'permissions' => []]);
        
        // Mock the socket driver
        $this->mock(SocketDriverInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getChannelName')->andReturn('presence-chat-room-1');
            $mock->shouldReceive('broadcast')->andReturn(true);
        });

        $this->roomService = app(RoomService::class);
        $this->messageService = app(MessageService::class);
    }

    public function test_send_message()
    {
        Event::fake([MessageSent::class]);

        $actorA = $this->createActorUser('client');
        $actorB = $this->createActorUser('client');

        $room = $this->roomService->findOrCreateDirectRoom($actorA, $actorB);
        
        $data = MessageData::fromArray([
            'body' => 'Hello B!',
            'type' => 'text',
        ]);

        $message = $this->messageService->send($room, $actorA, $data);

        $this->assertNotNull($message);
        $this->assertEquals('Hello B!', $message->body);
        $this->assertEquals($room->id, $message->room_id);
        
        Event::assertDispatched(MessageSent::class);

        // Room last_message_at was updated
        $room->refresh();
        $this->assertNotNull($room->last_message_at);
    }

    public function test_mark_as_read()
    {
        Event::fake([MessageRead::class]);

        $actorA = $this->createActorUser('client');
        $actorB = $this->createActorUser('client');

        $room = $this->roomService->findOrCreateDirectRoom($actorA, $actorB);
        
        $this->messageService->markAsRead($room, $actorB);

        Event::assertDispatched(MessageRead::class, function($e) use ($actorB) {
            return $e->actorId === $actorB->id;
        });

        $participant = $room->participants()->where('actor_id', $actorB->id)->first();
        $this->assertNotNull($participant->last_read_at);
    }
}

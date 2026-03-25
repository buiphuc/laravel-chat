<?php

namespace PhucBui\Chat\Tests\Unit\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use PhucBui\Chat\Events\MessageRead;
use PhucBui\Chat\Events\MessageSent;
use PhucBui\Chat\Events\RoomUpdated;
use PhucBui\Chat\Events\UserOnlineStatusChanged;
use PhucBui\Chat\Events\UserTyping;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Tests\TestCase;

class BroadcastEventsTest extends TestCase
{
    public function test_message_sent_event()
    {
        $actor = $this->createActorUser('client');
        $room = new ChatRoom();
        $room->id = 1;
        
        $message = new ChatMessage();
        $message->id = 55;
        $message->room_id = 1;
        $message->sender_type = $actor->getMorphClass();
        $message->sender_id = $actor->id;
        $message->type = 'text';
        $message->body = 'Hello World';

        $event = new MessageSent($message, $room);

        $this->assertEquals([new PrivateChannel('chat.room.1')], $event->broadcastOn());
        $this->assertEquals('message.sent', $event->broadcastAs());
        
        $payload = $event->broadcastWith();
        $this->assertEquals(55, $payload['message']['id']);
        $this->assertEquals('Hello World', $payload['message']['body']);
    }

    public function test_room_updated_event()
    {
        $room = new ChatRoom();
        $room->id = 10;
        $room->name = 'Test Room';
        $room->max_members = 5;

        $event = new RoomUpdated($room, 'participant_added');

        $this->assertEquals([new PrivateChannel('chat.room.10')], $event->broadcastOn());
        $this->assertEquals('room.updated', $event->broadcastAs());
        
        $payload = $event->broadcastWith();
        $this->assertEquals(10, $payload['room_id']);
        $this->assertEquals('participant_added', $payload['action']);
        $this->assertEquals('Test Room', $payload['name']);
        $this->assertEquals(5, $payload['max_members']);
    }

    public function test_message_read_event()
    {
        $room = new ChatRoom();
        $room->id = 20;

        $actor = $this->createActorUser('admin');

        $event = new MessageRead($room, $actor->getMorphClass(), $actor->id, '2023-10-10 10:10:10');

        $this->assertEquals([new PrivateChannel('chat.room.20')], $event->broadcastOn());
        $this->assertEquals('message.read', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertEquals(20, $payload['room_id']);
        $this->assertEquals($actor->getMorphClass(), $payload['actor_type']);
        $this->assertEquals($actor->id, $payload['actor_id']);
        $this->assertEquals('2023-10-10 10:10:10', $payload['read_at']); // Payload structure check!
    }

    public function test_user_typing_event()
    {
        $room = new ChatRoom();
        $room->id = 30;

        $actor = $this->createActorUser('client');

        $event = new UserTyping($room->id, $actor->getMorphClass(), $actor->id, true);

        $this->assertEquals([new PrivateChannel('chat.room.30')], $event->broadcastOn());
        $this->assertEquals('user.typing', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertEquals(30, $payload['room_id']);
        $this->assertEquals($actor->getMorphClass(), $payload['actor_type']);
        $this->assertEquals($actor->id, $payload['actor_id']);
        $this->assertTrue($payload['is_typing']);
    }

    public function test_user_online_status_changed_event()
    {
        $actor = $this->createActorUser('client');

        $event = new UserOnlineStatusChanged($actor->getMorphClass(), $actor->id, true);
        
        $this->assertEquals('user.online_status', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertEquals($actor->getMorphClass(), $payload['actor_type']);
        $this->assertEquals($actor->id, $payload['actor_id']);
        $this->assertTrue($payload['is_online']);
    }
}

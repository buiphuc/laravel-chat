<?php

namespace PhucBui\Chat\Tests\Unit\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use PhucBui\Chat\Drivers\ReverbDriver;
use PhucBui\Chat\Events\GenericBroadcast;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Tests\TestCase;

class ReverbDriverTest extends TestCase
{
    protected ReverbDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new ReverbDriver([]);
    }

    public function test_get_channel_name()
    {
        $room = new ChatRoom();
        $room->id = 77;
        $this->assertEquals('chat.room.77', $this->driver->getChannelName($room));
        $this->assertEquals('presence-chat.room.77', $this->driver->getPresenceChannelName($room));
    }

    public function test_broadcast_fires_generic_event()
    {
        Event::fake([GenericBroadcast::class]);

        $this->driver->broadcast('test-channel', 'test-event', ['hello' => 'world']);

        Event::assertDispatched(GenericBroadcast::class, function (GenericBroadcast $event) {
            return $event->broadcastOn()[0]->name === 'test-channel'
                && $event->broadcastAs() === 'test-event'
                && $event->data['hello'] === 'world';
        });
    }

    public function test_authenticate()
    {
        $request = Request::create('/auth');
        $response = $this->driver->authenticate($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['authenticated']);
    }
}

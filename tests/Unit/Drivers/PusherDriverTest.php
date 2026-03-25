<?php

namespace PhucBui\Chat\Tests\Unit\Drivers;

use Illuminate\Http\Request;
use PhucBui\Chat\Drivers\PusherDriver;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Tests\TestCase;
use Mockery;

class TestPusherDriver extends PusherDriver
{
    public $mockPusher;

    protected function getPusher()
    {
        if (!$this->mockPusher) {
            $this->mockPusher = Mockery::mock('Pusher\Pusher');
        }
        return $this->mockPusher;
    }
}

class PusherDriverTest extends TestCase
{
    protected TestPusherDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new TestPusherDriver([
            'key' => 'test-key',
            'secret' => 'test-secret',
            'app_id' => 'test-app',
            'cluster' => 'ap1',
        ]);
    }

    public function test_get_channel_name()
    {
        $room = new ChatRoom();
        $room->id = 99;
        $this->assertEquals('private-chat.room.99', $this->driver->getChannelName($room));
        $this->assertEquals('presence-chat.room.99', $this->driver->getPresenceChannelName($room));
    }

    public function test_broadcast()
    {
        $mock = Mockery::mock('Pusher\Pusher');
        $mock->shouldReceive('trigger')
             ->once()
             ->with('test-channel', 'test-event', ['foo' => 'bar']);

        $this->driver->mockPusher = $mock;

        $this->driver->broadcast('test-channel', 'test-event', ['foo' => 'bar']);
        // If it reaches here without throw, expectation is met
        $this->assertTrue(true);
    }

    public function test_authenticate()
    {
        $mock = Mockery::mock('Pusher\Pusher');
        $mock->shouldReceive('authorizeChannel')
             ->once()
             ->with('test-channel', '1234.5678')
             ->andReturn('{"auth":"test-auth-signature"}');

        $this->driver->mockPusher = $mock;

        $request = Request::create('/auth', 'POST', [
            'socket_id' => '1234.5678',
            'channel_name' => 'test-channel',
        ]);

        $response = $this->driver->authenticate($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test-auth-signature', json_decode($response->getContent(), true)['auth']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

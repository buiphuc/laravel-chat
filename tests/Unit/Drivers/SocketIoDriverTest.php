<?php

namespace PhucBui\Chat\Tests\Unit\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhucBui\Chat\Drivers\SocketIoDriver;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Tests\TestCase;

class SocketIoDriverTest extends TestCase
{
    protected SocketIoDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new SocketIoDriver([
            'server_url' => 'http://localhost:3000',
            'api_key' => 'secret123',
        ]);
    }

    public function test_get_channel_name()
    {
        $room = new ChatRoom();
        $room->id = 55;
        $this->assertEquals('chat_room_55', $this->driver->getChannelName($room));
        $this->assertEquals('presence_chat_room_55', $this->driver->getPresenceChannelName($room));
    }

    public function test_broadcast_sends_http_request()
    {
        Http::fake([
            'http://localhost:3000/broadcast' => Http::response(['success' => true], 200),
        ]);

        $this->driver->broadcast('test-channel', 'test-event', ['data' => 'test']);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://localhost:3000/broadcast'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer secret123')
                && $request['channel'] === 'test-channel'
                && $request['event'] === 'test-event'
                && $request['data'] === ['data' => 'test'];
        });
    }

    public function test_authenticate_sends_http_request()
    {
        Http::fake([
            'http://localhost:3000/auth' => Http::response(['auth' => 'valid'], 200),
        ]);

        $request = Request::create('/auth', 'POST', [
            'socket_id' => '12345',
            'channel_name' => 'test-channel',
        ]);

        $response = $this->driver->authenticate($request);

        Http::assertSent(function ($req) {
            return $req->url() === 'http://localhost:3000/auth'
                && $req->method() === 'POST'
                && $req['socket_id'] === '12345'
                && $req['channel_name'] === 'test-channel';
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('valid', json_decode($response->getContent(), true)['auth']);
    }
}

<?php

namespace PhucBui\Chat\Drivers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhucBui\Chat\Contracts\SocketDriverInterface;
use PhucBui\Chat\Models\ChatRoom;

class PusherDriver implements SocketDriverInterface
{
    protected $pusher = null;

    public function __construct(protected array $config = [])
    {
    }

    protected function getPusher()
    {
        if ($this->pusher === null) {
            $this->pusher = new \Pusher\Pusher(
                $this->config['key'] ?? '',
                $this->config['secret'] ?? '',
                $this->config['app_id'] ?? '',
                [
                    'cluster' => $this->config['cluster'] ?? 'ap1',
                    'useTLS' => true,
                ]
            );
        }

        return $this->pusher;
    }

    public function broadcast(string $channel, string $event, array $data): void
    {
        $this->getPusher()->trigger($channel, $event, $data);
    }

    public function getChannelName(ChatRoom $room): string
    {
        return "private-chat.room.{$room->id}";
    }

    public function getPresenceChannelName(ChatRoom $room): string
    {
        return "presence-chat.room.{$room->id}";
    }

    public function authenticate(Request $request): JsonResponse
    {
        $socketId = $request->input('socket_id');
        $channelName = $request->input('channel_name');

        $auth = $this->getPusher()->authorizeChannel($channelName, $socketId);

        return response()->json(json_decode($auth, true));
    }
}

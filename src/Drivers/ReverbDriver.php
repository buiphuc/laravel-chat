<?php

namespace PhucBui\Chat\Drivers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use PhucBui\Chat\Contracts\SocketDriverInterface;
use PhucBui\Chat\Models\ChatRoom;

class ReverbDriver implements SocketDriverInterface
{
    public function __construct(protected array $config = [])
    {
    }

    public function broadcast(string $channel, string $event, array $data): void
    {
        broadcast(new \PhucBui\Chat\Events\GenericBroadcast($channel, $event, $data));
    }

    public function getChannelName(ChatRoom $room): string
    {
        return "chat.room.{$room->id}";
    }

    public function getPresenceChannelName(ChatRoom $room): string
    {
        return "presence-chat.room.{$room->id}";
    }

    public function authenticate(Request $request): JsonResponse
    {
        return response()->json(['authenticated' => true]);
    }
}

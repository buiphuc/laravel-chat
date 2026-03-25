<?php

namespace PhucBui\Chat\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhucBui\Chat\Models\ChatRoom;

interface SocketDriverInterface
{
    /**
     * Broadcast data to a channel.
     */
    public function broadcast(string $channel, string $event, array $data): void;

    /**
     * Get the private channel name for a room.
     */
    public function getChannelName(ChatRoom $room): string;

    /**
     * Get the presence channel name for a room.
     */
    public function getPresenceChannelName(ChatRoom $room): string;

    /**
     * Authenticate a socket connection request.
     */
    public function authenticate(Request $request): JsonResponse;
}

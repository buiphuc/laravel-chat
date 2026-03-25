<?php

namespace PhucBui\Chat\Drivers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhucBui\Chat\Contracts\SocketDriverInterface;
use PhucBui\Chat\Models\ChatRoom;

class SocketIoDriver implements SocketDriverInterface
{
    protected string $serverUrl;
    protected ?string $apiKey;

    public function __construct(protected array $config = [])
    {
        $this->serverUrl = rtrim($config['server_url'] ?? 'http://localhost:3000', '/');
        $this->apiKey = $config['api_key'] ?? null;
    }

    public function broadcast(string $channel, string $event, array $data): void
    {
        $headers = [];
        if ($this->apiKey) {
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        Http::withHeaders($headers)
            ->post("{$this->serverUrl}/broadcast", [
                'channel' => $channel,
                'event' => $event,
                'data' => $data,
            ]);
    }

    public function getChannelName(ChatRoom $room): string
    {
        return "chat_room_{$room->id}";
    }

    public function getPresenceChannelName(ChatRoom $room): string
    {
        return "presence_chat_room_{$room->id}";
    }

    public function authenticate(Request $request): JsonResponse
    {
        $headers = [];
        if ($this->apiKey) {
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        $response = Http::withHeaders($headers)
            ->post("{$this->serverUrl}/auth", [
                'socket_id' => $request->input('socket_id'),
                'channel_name' => $request->input('channel_name'),
            ]);

        return response()->json($response->json(), $response->status());
    }
}

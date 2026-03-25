<?php

namespace PhucBui\Chat\Tests\Feature;

use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatParticipant;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MessageApiTest extends TestCase
{
    protected array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        
        \PhucBui\Chat\Models\ChatRole::create([
            'name' => 'member',
            'display_name' => 'Member',
            'permissions' => ['send_message'],
            'is_default' => true,
        ]);

        $path = __DIR__ . '/../Fixtures/messages.json';
        if (File::exists($path)) {
            $this->fixtures = json_decode(File::get($path), true);
        } else {
            $this->fixtures = [];
        }
    }

    protected function getFixture(string $key): array
    {
        return $this->fixtures[$key] ?? [];
    }

    private function createRoomWithParticipant($user): ChatRoom
    {
        $room = ChatRoom::create([
            'name' => 'Test Room',
            'max_members' => 5,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        $role = \PhucBui\Chat\Models\ChatRole::first();

        ChatParticipant::create([
            'room_id' => $room->id,
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        return $room;
    }

    public function test_list_messages()
    {
        $fixture = $this->getFixture('list_messages');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $room = $this->createRoomWithParticipant($user);

        // create 5 messages
        for ($i = 0; $i < 5; $i++) {
            ChatMessage::create([
                'room_id' => $room->id,
                'sender_type' => $user->getMorphClass(),
                'sender_id' => $user->id,
                'body' => 'Message ' . $i,
                'type' => 'text',
            ]);
        }

        $url = str_replace('{room}', $room->id, $fixture['url']);
        
        $response = $this->actingAs($user)
            ->json($fixture['method'], $url);

        $response->assertStatus($fixture['expected_status']);
        $response->assertJsonCount(5, 'data');
    }

    public function test_list_messages_unauthorized()
    {
        $fixture = $this->getFixture('list_messages_unauthorized');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        
        $otherUser = $this->createActorUser('admin');
        $room = $this->createRoomWithParticipant($otherUser);

        $url = str_replace('{room}', $room->id, $fixture['url']);
        
        $response = $this->actingAs($user)
            ->json($fixture['method'], $url);

        $response->assertStatus($fixture['expected_status']);
    }

    public function test_send_message()
    {
        $fixture = $this->getFixture('send_message');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $room = $this->createRoomWithParticipant($user);

        $url = str_replace('{room}', $room->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 201) {
                $this->assertDatabaseHas('chat_messages', [
                    'room_id' => $room->id,
                    'body' => $payload['data']['body']
                ]);
            }
        }
    }

    public function test_mark_as_read()
    {
        $fixture = $this->getFixture('mark_as_read');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $room = $this->createRoomWithParticipant($user);

        $msg = ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Unread Message',
            'type' => 'text',
        ]);

        $url = str_replace('{room}', $room->id, $fixture['url']);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $url);

        $response->assertStatus($fixture['expected_status']);
        
        $participant = ChatParticipant::where('room_id', $room->id)
            ->where('actor_id', $user->id)
            ->first();
        
        $this->assertNotNull($participant->last_read_at);
    }

    public function test_typing()
    {
        $fixture = $this->getFixture('typing');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $room = $this->createRoomWithParticipant($user);

        $url = str_replace('{room}', $room->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);
        }
    }

    public function test_search_messages()
    {
        $fixture = $this->getFixture('search_messages');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        config(['chat.actors.client.capabilities.can_search_messages' => true]);

        $room = $this->createRoomWithParticipant($user);
        ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Hello World',
            'type' => 'text',
        ]);
        ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Something else',
            'type' => 'text',
        ]);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $fixture['url'], ['keyword' => 'World', 'room_id' => $room->id]);

        $response->assertStatus($fixture['expected_status']);
        $response->assertJsonCount(1, 'data');
    }
}

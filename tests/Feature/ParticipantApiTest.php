<?php

namespace PhucBui\Chat\Tests\Feature;

use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatParticipant;
use PhucBui\Chat\Models\ChatRole;
use PhucBui\Chat\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ParticipantApiTest extends TestCase
{
    protected array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        
        ChatRole::create([
            'name' => 'member',
            'display_name' => 'Member',
            'permissions' => ['send_message'],
            'is_default' => true,
        ]);
        
        ChatRole::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'permissions' => ['send_message', 'remove_participant'],
            'is_default' => false,
        ]);

        $path = __DIR__ . '/../Fixtures/participants.json';
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

        $role = ChatRole::where('name', 'member')->first();

        ChatParticipant::create([
            'room_id' => $room->id,
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        return $room;
    }

    public function test_list_participants()
    {
        $fixture = $this->getFixture('list_participants');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $room = $this->createRoomWithParticipant($user);

        $url = str_replace('{room}', $room->id, $fixture['url']);
        
        $response = $this->actingAs($user)
            ->json($fixture['method'], $url);

        $response->assertStatus($fixture['expected_status']);
        $response->assertJsonCount(1, 'data');
    }

    public function test_add_participant()
    {
        $fixture = $this->getFixture('add_participant');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $target = $this->createActorUser('client');
        
        // Admin user can bypass membership check if they have can_see_all_rooms or we add them.
        config(['chat.actors.admin.capabilities.can_see_all_rooms' => true]);
        
        $room = $this->createRoomWithParticipant($user);

        $url = str_replace('{room}', $room->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $data = $payload['data'];
            if (isset($data['actor_id']) && $data['actor_id'] === '{actor_id}') {
                $data['actor_id'] = $target->id;
            }

            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $data);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 201) {
                $this->assertDatabaseHas('chat_participants', [
                    'room_id' => $room->id,
                    'actor_type' => $target->getMorphClass(),
                    'actor_id' => $target->id,
                ]);
            }
        }
    }

    public function test_add_participant_unauthorized()
    {
        $fixture = $this->getFixture('add_participant_unauthorized');
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

    public function test_update_participant_role()
    {
        $fixture = $this->getFixture('update_participant_role');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $target = $this->createActorUser('client');
        
        config(['chat.actors.super_admin.capabilities.can_see_all_rooms' => true]);
        // Default route prefix for super_admin is not set in array, it will be api/super_admin/chat
        // which matches the URL in the fixture.
        
        $room = $this->createRoomWithParticipant($user);
        
        $role = ChatRole::where('name', 'member')->first();
        $targetParticipant = ChatParticipant::create([
            'room_id' => $room->id,
            'actor_type' => $target->getMorphClass(),
            'actor_id' => $target->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        $url = str_replace(['{room}', '{participant}'], [$room->id, $targetParticipant->id], $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 200) {
                $adminRole = ChatRole::where('name', 'admin')->first();
                $this->assertDatabaseHas('chat_participants', [
                    'id' => $targetParticipant->id,
                    'role_id' => $adminRole->id,
                ]);
            }
        }
    }

    public function test_remove_participant()
    {
        $fixture = $this->getFixture('remove_participant');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // admin
        $target = $this->createActorUser('client');
        
        config(['chat.actors.admin.capabilities.can_see_all_rooms' => true]);
        
        $room = $this->createRoomWithParticipant($user);
        
        $role = ChatRole::where('name', 'member')->first();
        $targetParticipant = ChatParticipant::create([
            'room_id' => $room->id,
            'actor_type' => $target->getMorphClass(),
            'actor_id' => $target->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        $url = str_replace(['{room}', '{participant}'], [$room->id, $targetParticipant->id], $fixture['url']);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $url);

        $response->assertStatus($fixture['expected_status']);

        $this->assertDatabaseMissing('chat_participants', [
            'id' => $targetParticipant->id,
        ]);
    }
}

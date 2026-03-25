<?php

namespace PhucBui\Chat\Tests\Feature;

use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatParticipant;
use PhucBui\Chat\Tests\TestCase;
use Illuminate\Support\Facades\File;

class RoomApiTest extends TestCase
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

        $path = __DIR__ . '/../Fixtures/rooms.json';
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

    public function test_list_rooms_admin()
    {
        $fixture = $this->getFixture('list_rooms_admin');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // admin
        config(['chat.actors.admin.capabilities.can_see_all_rooms' => true]);

        // Create 2 rooms
        $room1 = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);
        $room2 = ChatRoom::create([
            'name' => 'Group Room',
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        // Admin should see all rooms
        $response = $this->actingAs($user)
            ->json($fixture['method'], $fixture['url']);

        $response->assertStatus($fixture['expected_status']);
        $response->assertJsonCount(2, 'data');
    }

    public function test_list_rooms_client()
    {
        $fixture = $this->getFixture('list_rooms_client');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // client
        
        // Create 2 rooms, client is in room1 only
        $room1 = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);
        
        $role = \PhucBui\Chat\Models\ChatRole::first();

        ChatParticipant::create([
            'room_id' => $room1->id,
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        $room2 = ChatRoom::create([
            'name' => 'Group Room',
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $fixture['url']);

        $response->assertStatus($fixture['expected_status']);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($room1->id, $response->json('data.0.id'));
    }

    public function test_create_direct_room()
    {
        $fixture = $this->getFixture('create_direct_room');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // client
        $targetUser = $this->createActorUser('admin');

        foreach ($fixture['payloads'] as $payload) {
            $data = $payload['data'];
            // Replace placeholder
            if (isset($data['target_id']) && $data['target_id'] === '{target_id}') {
                $data['target_id'] = $targetUser->id;
            }

            $response = $this->actingAs($user)
                ->json($fixture['method'], $fixture['url'], $data);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 201) {
                // Verify DB
                $this->assertDatabaseHas('chat_rooms', ['max_members' => 2]);
            }
        }
    }

    public function test_create_group_room_admin()
    {
        $fixture = $this->getFixture('create_group_room_admin');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // admin
        $member1 = $this->createActorUser('client');
        $member2 = $this->createActorUser('client');

        foreach ($fixture['payloads'] as $payload) {
            $data = $payload['data'];
            // Replace placeholders in participant_ids
            if (isset($data['participant_ids'])) {
                $data['participant_ids'] = [$member1->id, $member2->id];
            }

            $response = $this->actingAs($user)
                ->json($fixture['method'], $fixture['url'], $data);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 201) {
                $this->assertDatabaseHas('chat_rooms', [
                    'name' => 'Test Group',
                    'max_members' => 5,
                ]);
            }
        }
    }

    public function test_update_room()
    {
        $fixture = $this->getFixture('update_room');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // admin
        $room = ChatRoom::create([
            'name' => 'Old Name', 
            'max_members' => 5,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);
        
        // Admin needs to be participant as Owner/Admin role actually... wait!
        // Admin capability can_manage_participants works even if not participant?
        // Wait, ChatRoomAccessMiddleware allows super_admin to bypass room membership checks!
        config(['chat.actors.admin.capabilities.can_see_all_rooms' => true]);

        foreach ($fixture['payloads'] as $payload) {
            $url = str_replace('{room}', $room->id, $fixture['url']);
            
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 200) {
                $this->assertDatabaseHas('chat_rooms', ['id' => $room->id, 'name' => 'New Name']);
            }
        }
    }

    public function test_update_room_unauthorized()
    {
        $fixture = $this->getFixture('update_room_unauthorized');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']); // client
        $room = ChatRoom::create([
            'name' => 'Old Name', 
            'max_members' => 5,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);
        
        $role = \PhucBui\Chat\Models\ChatRole::first();

        // Add user to room
        ChatParticipant::create([
            'room_id' => $room->id,
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        foreach ($fixture['payloads'] as $payload) {
            $url = str_replace('{room}', $room->id, $fixture['url']);
            
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);
        }
    }

    public function test_delete_room()
    {
        $fixtureAdmin = $this->getFixture('delete_room');
        $fixtureClient = $this->getFixture('delete_room_unauthorized');
        
        $userAdmin = $this->createActorUser($fixtureAdmin['auth']);
        $userClient = $this->createActorUser($fixtureClient['auth']);
        
        config(['chat.actors.admin.capabilities.can_see_all_rooms' => true]); // bypass membership

        $room = ChatRoom::create([
            'name' => 'To be deleted',
            'created_by_type' => $userAdmin->getMorphClass(),
            'created_by_id' => $userAdmin->id,
        ]);
        
        $role = \PhucBui\Chat\Models\ChatRole::first();

        // add client to room
        ChatParticipant::create([
            'room_id' => $room->id,
            'actor_type' => $userClient->getMorphClass(),
            'actor_id' => $userClient->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        // Client attempts to delete
        $urlClient = str_replace('{room}', $room->id, $fixtureClient['url']);
        $response1 = $this->actingAs($userClient)
            ->json($fixtureClient['method'], $urlClient);
        $response1->assertStatus($fixtureClient['expected_status']); // 403

        // Admin attempts to delete
        $urlAdmin = str_replace('{room}', $room->id, $fixtureAdmin['url']);
        $response2 = $this->actingAs($userAdmin)
            ->json($fixtureAdmin['method'], $urlAdmin);
        $response2->assertStatus($fixtureAdmin['expected_status']); // 200

        $this->assertSoftDeleted('chat_rooms', ['id' => $room->id]);
    }
}

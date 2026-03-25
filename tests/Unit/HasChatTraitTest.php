<?php

declare(strict_types=1);

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatParticipant;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatBlockedUser;
use PhucBui\Chat\ChatManager;

class HasChatTraitTest extends TestCase
{
    public function test_get_chat_display_name_uses_actor_definition(): void
    {
        // ARRANGE
        $user = $this->createActorUser('client');
        
        // Let's set the resolver return value
        app(ChatManager::class)->resolveActorUsing('client', function() use ($user) {
            return collect(['name' => 'Custom Display Name']);
        });

        // Config display_name fallback to a property
        config(['chat.actors.client.display_name' => 'name']);

        // ACT & ASSERT
        // Note: The trait calls ChatManager::getDisplayName which defaults to $actor->name if config is empty.
        $this->assertEquals($user->name, $user->getChatDisplayName());
    }

    public function test_morph_relations_work_correctly(): void
    {
        // ARRANGE
        $user = $this->createActorUser('client');

        // Create a room
        $room = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
            'actor_type' => $user->getMorphClass(), // actor is the contextual participant type setting config
            'actor_id' => $user->id, // Some configurations use these for poly relations
        ]);

        // Create a role first
        $role = \PhucBui\Chat\Models\ChatRole::create(['name' => 'admin', 'display_name' => 'Admin', 'permissions' => []]);

        // Attach participant using Eloquent relation directly
        $participant = $room->participants()->create([
            'actor_type' => $user->getMorphClass(),
            'actor_id' => $user->id,
            'role_id' => $role->id,
            'joined_at' => now(),
        ]);

        // Create a message sent by user
        $message = $room->messages()->create([
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Hello',
        ]);

        // Create a blocked user record
        $blocked = ChatBlockedUser::create([
            'blocker_type' => $user->getMorphClass(),
            'blocker_id' => $user->id,
            'blocked_type' => $user->getMorphClass(), // Blocking another TestUser theoretically
            'blocked_id' => 999, 
        ]);

        // ACT & ASSERT: Check the trait relation methods
        $this->assertCount(1, $user->chatRooms);
        $this->assertEquals($room->id, $user->chatRooms->first()->id);

        $this->assertCount(1, $user->chatParticipations);
        $this->assertEquals($participant->id, $user->chatParticipations->first()->id);

        $this->assertCount(1, $user->chatMessages);
        $this->assertEquals($message->id, $user->chatMessages->first()->id);

        $this->assertCount(1, $user->chatBlockedUsers);
        $this->assertEquals($blocked->id, $user->chatBlockedUsers->first()->id);
    }
}

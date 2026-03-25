<?php

declare(strict_types=1);

namespace PhucBui\Chat\Tests\Unit;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\DTOs\RoomData;
use PhucBui\Chat\DTOs\MessageData;
use PhucBui\Chat\DTOs\ParticipantData;

class DTOsTest extends TestCase
{
    public function test_room_data_from_array(): void
    {
        $data = RoomData::fromArray([
            'name' => 'Project Alpha',
            'max_members' => 5,
            'metadata' => ['theme' => 'dark'],
            'participant_ids' => [1, 2, 3],
            'participant_type' => 'App\\Models\\User'
        ]);

        $this->assertEquals('Project Alpha', $data->name);
        $this->assertEquals(5, $data->maxMembers);
        $this->assertEquals(['theme' => 'dark'], $data->metadata);
        $this->assertEquals([1, 2, 3], $data->participantIds);
        $this->assertEquals('App\\Models\\User', $data->participantType);
    }

    public function test_room_data_defaults(): void
    {
        $data = RoomData::fromArray([]);

        $this->assertNull($data->name);
        $this->assertNull($data->maxMembers);
        $this->assertNull($data->metadata);
        $this->assertEquals([], $data->participantIds);
        $this->assertNull($data->participantType);
    }

    public function test_message_data_from_array(): void
    {
        $data = MessageData::fromArray([
            'body' => 'Hello World',
            'type' => 'text',
            'parent_id' => 42,
            'metadata' => ['urgent' => true]
        ]);

        $this->assertEquals('text', $data->type);
        $this->assertEquals('Hello World', $data->body);
        $this->assertEquals(42, $data->parentId);
        $this->assertEquals(['urgent' => true], $data->metadata);
    }

    public function test_message_data_defaults(): void
    {
        $data = MessageData::fromArray([
            'body' => 'Only body'
        ]);

        $this->assertEquals('Only body', $data->body);
        $this->assertEquals('text', $data->type); // Default
        $this->assertNull($data->parentId);
    }

    public function test_participant_data_from_array(): void
    {
        $data = ParticipantData::fromArray([
            'actor_type' => 'App\\Models\\Customer',
            'actor_id' => 99,
            'role_name' => 'admin'
        ]);

        $this->assertEquals('App\\Models\\Customer', $data->actorType);
        $this->assertEquals(99, $data->actorId);
        $this->assertEquals('admin', $data->roleName);
    }

    public function test_participant_data_defaults(): void
    {
        $data = ParticipantData::fromArray([
            'actor_type' => 'User',
            'actor_id' => 1
        ]);

        $this->assertEquals('User', $data->actorType);
        $this->assertEquals(1, $data->actorId);
        $this->assertEquals('member', $data->roleName);
    }
}

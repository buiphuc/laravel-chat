<?php

namespace PhucBui\Chat\Tests\Feature;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Models\ChatBlockedUser;
use Illuminate\Support\Facades\File;

class BlockApiTest extends TestCase
{
    protected array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        
        $path = __DIR__ . '/../Fixtures/blocks.json';
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

    public function test_block_user()
    {
        $fixture = $this->getFixture('block_user');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $target = $this->createActorUser('client');

        $url = str_replace('{user}', $target->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 201) {
                $this->assertDatabaseHas('chat_blocked_users', [
                    'blocker_id' => $user->id,
                    'blocked_id' => $target->id,
                    'reason' => 'Spam',
                ]);
            }
        }
    }

    public function test_block_user_unauthorized()
    {
        $fixture = $this->getFixture('block_user_unauthorized');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $target = $this->createActorUser('client');
        
        config(['chat.actors.client.capabilities.can_block_users' => false]);

        $url = str_replace('{user}', $target->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);
        }
    }

    public function test_unblock_user()
    {
        $fixture = $this->getFixture('unblock_user');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $target = $this->createActorUser('client');
        
        ChatBlockedUser::create([
            'blocker_type' => $user->getMorphClass(),
            'blocker_id' => $user->id,
            'blocked_type' => $target->getMorphClass(),
            'blocked_id' => $target->id,
        ]);

        $url = str_replace('{user}', $target->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            $this->assertDatabaseMissing('chat_blocked_users', [
                'blocker_id' => $user->id,
                'blocked_id' => $target->id,
            ]);
        }
    }

    public function test_list_blocked_users()
    {
        $fixture = $this->getFixture('list_blocked_users');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $target = $this->createActorUser('client');
        
        ChatBlockedUser::create([
            'blocker_type' => $user->getMorphClass(),
            'blocker_id' => $user->id,
            'blocked_type' => $target->getMorphClass(),
            'blocked_id' => $target->id,
        ]);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $fixture['url']);

        $response->assertStatus($fixture['expected_status']);
        $response->assertJsonCount(1, 'data');
    }
}

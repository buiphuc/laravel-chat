<?php

namespace PhucBui\Chat\Tests\Feature;

use PhucBui\Chat\Tests\TestCase;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatMessageReport;
use Illuminate\Support\Facades\File;

class ReportApiTest extends TestCase
{
    protected array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        
        $path = __DIR__ . '/../Fixtures/reports.json';
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

    private function createMessage($user): ChatMessage
    {
        $room = ChatRoom::create([
            'max_members' => 2,
            'created_by_type' => $user->getMorphClass(),
            'created_by_id' => $user->id,
        ]);

        return ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $user->getMorphClass(),
            'sender_id' => $user->id,
            'body' => 'Test message',
            'type' => 'text',
        ]);
    }

    public function test_report_message()
    {
        $fixture = $this->getFixture('report_message');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $msg = $this->createMessage($user);

        $url = str_replace('{message}', $msg->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 201) {
                $this->assertDatabaseHas('chat_message_reports', [
                    'message_id' => $msg->id,
                    'reporter_id' => $user->id,
                    'reason' => 'Abusive content',
                    'status' => 'pending',
                ]);
            }
        }
    }

    public function test_list_reports()
    {
        $fixture = $this->getFixture('list_reports');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $msg = $this->createMessage($user);
        
        // Ensure user has capability although super_admin default has it
        config(['chat.actors.super_admin.capabilities.can_review_reports' => true]);

        ChatMessageReport::create([
            'message_id' => $msg->id,
            'reporter_type' => $user->getMorphClass(),
            'reporter_id' => $user->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $fixture['url']);

        $response->assertStatus($fixture['expected_status']);
        
        // Assert json has 1 report in 'data' array
        $response->assertJsonCount(1, 'data');
    }

    public function test_list_reports_unauthorized()
    {
        $fixture = $this->getFixture('list_reports_unauthorized');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        config(['chat.actors.client.capabilities.can_review_reports' => false]);

        $response = $this->actingAs($user)
            ->json($fixture['method'], $fixture['url']);

        $response->assertStatus($fixture['expected_status']);
    }

    public function test_review_report()
    {
        $fixture = $this->getFixture('review_report');
        if (empty($fixture)) $this->markTestSkipped('Fixture missing');

        $user = $this->createActorUser($fixture['auth']);
        $msg = $this->createMessage($user);

        $report = ChatMessageReport::create([
            'message_id' => $msg->id,
            'reporter_type' => $user->getMorphClass(),
            'reporter_id' => $user->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $url = str_replace('{report}', $report->id, $fixture['url']);

        foreach ($fixture['payloads'] as $payload) {
            $response = $this->actingAs($user)
                ->json($fixture['method'], $url, $payload['data']);

            $response->assertStatus($payload['expected_status']);

            if ($payload['expected_status'] === 200) {
                $this->assertDatabaseHas('chat_message_reports', [
                    'id' => $report->id,
                    'status' => 'reviewed',
                    'reviewer_id' => $user->id,
                ]);
            }
        }
    }
}

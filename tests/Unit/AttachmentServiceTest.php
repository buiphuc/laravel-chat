<?php

namespace PhucBui\Chat\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Services\AttachmentService;
use PhucBui\Chat\Tests\TestCase;

class AttachmentServiceTest extends TestCase
{
    protected AttachmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AttachmentService::class);
    }

    public function test_upload_and_delete()
    {
        Storage::fake('public');

        $actor = $this->createActorUser('client');
        $room = ChatRoom::create(['max_members' => 2, 'created_by_type' => $actor->getMorphClass(), 'created_by_id' => $actor->id]);
        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $actor->getMorphClass(),
            'sender_id' => $actor->id,
            'body' => 'Check this file out',
        ]);

        $file = UploadedFile::fake()->image('photo.jpg')->size(1024); // 1MB

        $attachment = $this->service->upload($message, $file);

        $this->assertNotNull($attachment);
        $this->assertEquals('photo.jpg', $attachment->file_name);
        $this->assertEquals('image/jpeg', $attachment->file_type);
        Storage::disk('public')->assertExists($attachment->file_path);

        // Test delete
        $this->service->delete($attachment);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_max_size_validation()
    {
        config(['chat.attachments.max_size' => 1024]); // 1MB max

        $actor = $this->createActorUser('client');
        $room = ChatRoom::create(['max_members' => 2, 'created_by_type' => $actor->getMorphClass(), 'created_by_id' => $actor->id]);
        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $actor->getMorphClass(),
            'sender_id' => $actor->id,
            'body' => 'Big file',
        ]);

        $file = UploadedFile::fake()->create('big.pdf', 2048); // 2MB

        $this->expectException(\RuntimeException::class);
        $this->service->upload($message, $file);
    }

    public function test_type_validation()
    {
        config(['chat.attachments.allowed_types' => ['image/*']]);

        $actor = $this->createActorUser('client');
        $room = ChatRoom::create(['max_members' => 2, 'created_by_type' => $actor->getMorphClass(), 'created_by_id' => $actor->id]);
        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_type' => $actor->getMorphClass(),
            'sender_id' => $actor->id,
            'body' => 'Bad file',
        ]);

        // Attempting to upload a PDF when only images are allowed
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $this->expectException(\RuntimeException::class);
        $this->service->upload($message, $file);
    }
}

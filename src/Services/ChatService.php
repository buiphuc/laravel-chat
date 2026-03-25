<?php

namespace PhucBui\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\ChatServiceInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoomRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Contracts\Repositories\ChatRoleRepositoryInterface;
use PhucBui\Chat\DTOs\MessageData;
use PhucBui\Chat\DTOs\RoomData;
use PhucBui\Chat\Models\ChatMessage;
use PhucBui\Chat\Models\ChatRoom;

class ChatService implements ChatServiceInterface
{
    public function __construct(
        protected RoomService $roomService,
        protected MessageService $messageService,
        protected ChatRoomRepositoryInterface $roomRepository,
        protected ChatParticipantRepositoryInterface $participantRepository,
        protected ChatRoleRepositoryInterface $roleRepository,
    ) {
    }

    public function findOrCreateDirectRoom(Model $actorA, Model $actorB): ChatRoom
    {
        return $this->roomService->findOrCreateDirectRoom($actorA, $actorB);
    }

    public function createGroupRoom(RoomData $data, Model $creator): ChatRoom
    {
        return $this->roomService->createGroupRoom($data, $creator);
    }

    public function sendMessage(ChatRoom $room, Model $sender, MessageData $data): ChatMessage
    {
        return $this->messageService->send($room, $sender, $data);
    }

    public function getMessages(ChatRoom $room, int $perPage = 50): LengthAwarePaginator
    {
        return $this->messageService->getMessages($room, $perPage);
    }

    public function getRoomsForActor(Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        return $this->roomRepository->getRoomsByActor($actor, $perPage);
    }

    public function markAsRead(ChatRoom $room, Model $actor): void
    {
        $this->messageService->markAsRead($room, $actor);
    }
}

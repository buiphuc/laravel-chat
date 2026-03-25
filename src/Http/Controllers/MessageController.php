<?php

namespace PhucBui\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhucBui\Chat\DTOs\MessageData;
use PhucBui\Chat\Events\UserTyping;
use PhucBui\Chat\Http\Requests\SearchMessageRequest;
use PhucBui\Chat\Http\Requests\StoreMessageRequest;
use PhucBui\Chat\Http\Resources\MessageResource;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Services\AttachmentService;
use PhucBui\Chat\Services\MessageService;
use PhucBui\Chat\Services\SearchService;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService,
        protected AttachmentService $attachmentService,
        protected SearchService $searchService,
    ) {
    }

    /**
     * Get messages for a room.
     */
    public function index(Request $request, ChatRoom $room): JsonResponse
    {
        $perPage = $request->input('per_page', config('chat.messages.per_page', 50));
        $messages = $this->messageService->getMessages($room, $perPage);

        return MessageResource::collection($messages)->response();
    }

    /**
     * Send a message to a room.
     */
    public function store(StoreMessageRequest $request, ChatRoom $room): JsonResponse
    {
        $actor = $request->input('chat_actor');

        $data = MessageData::fromArray($request->all());
        $message = $this->messageService->send($room, $actor, $data);

        // Handle attachments
        if ($request->hasFile('attachments') && config('chat.attachments.enabled', true)) {
            foreach ($request->file('attachments') as $file) {
                $this->attachmentService->upload($message, $file);
            }
            $message->load('attachments');
        }

        return (new MessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mark room as read.
     */
    public function markAsRead(Request $request, ChatRoom $room): JsonResponse
    {
        $actor = $request->input('chat_actor');
        $this->messageService->markAsRead($room, $actor);

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * Typing indicator.
     */
    public function typing(Request $request, ChatRoom $room): JsonResponse
    {
        $actor = $request->input('chat_actor');

        event(new UserTyping(
            $room->id,
            $actor->getMorphClass(),
            $actor->getKey(),
            $request->input('is_typing', true)
        ));

        return response()->json(['message' => 'OK']);
    }

    /**
     * Search messages.
     */
    public function search(SearchMessageRequest $request): JsonResponse
    {

        $messages = $this->searchService->search(
            $request->input('keyword'),
            $request->input('room_id'),
            null,
            $request->input('from_date'),
            $request->input('to_date'),
        );

        return MessageResource::collection($messages)->response();
    }
}

<?php

namespace PhucBui\Chat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Contracts\Repositories\ChatParticipantRepositoryInterface;
use PhucBui\Chat\Models\ChatRoom;

class ChatRoomAccessMiddleware
{
    public function __construct(
        protected ChatManager $chatManager,
        protected ChatParticipantRepositoryInterface $participantRepository,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $room = $request->route('room');
        $actor = $request->input('chat_actor');
        $actorName = $request->input('chat_actor_name');

        if (!$room instanceof ChatRoom) {
            $room = ChatRoom::findOrFail($room);
            $request->route()->setParameter('room', $room);
        }

        // Super admin with can_see_all_rooms can access any room
        if ($this->chatManager->hasCapability($actorName, 'can_see_all_rooms')) {
            return $next($request);
        }

        // Check if actor is a member of the room
        if (!$this->participantRepository->isMember($room->id, $actor)) {
            abort(403, 'You are not a member of this room.');
        }

        return $next($request);
    }
}

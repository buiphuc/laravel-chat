<?php

namespace PhucBui\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Contracts\Repositories\ChatRoleRepositoryInterface;
use PhucBui\Chat\Http\Requests\StoreParticipantRequest;
use PhucBui\Chat\Http\Requests\UpdateParticipantRoleRequest;
use PhucBui\Chat\Http\Resources\ParticipantResource;
use PhucBui\Chat\Models\ChatRoom;
use PhucBui\Chat\Services\RoomService;

class ParticipantController extends Controller
{
    public function __construct(
        protected RoomService $roomService,
        protected ChatManager $chatManager,
        protected ChatRoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * List participants in a room.
     */
    public function index(Request $request, ChatRoom $room): JsonResponse
    {
        $participants = $room->participants()->with(['actor', 'role'])->get();

        return ParticipantResource::collection($participants)->response();
    }

    /**
     * Add a participant to a room.
     */
    public function store(StoreParticipantRequest $request, ChatRoom $room): JsonResponse
    {
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_manage_participants')) {
            abort(403, trans('chat::messages.forbidden_manage_participants'));
        }

        $modelClass = $request->input('actor_type');
        $actor = $modelClass::findOrFail($request->input('actor_id'));

        $roleId = null;
        if ($request->has('role_name')) {
            $role = $this->roleRepository->findByName($request->input('role_name'));
            $roleId = $role?->id;
        }

        $this->roomService->addParticipant($room, $actor, $roleId);

        return response()->json(['message' => 'Participant added.'], 201);
    }

    /**
     * Update participant role (super_admin only).
     */
    public function update(UpdateParticipantRoleRequest $request, ChatRoom $room, int $participantId): JsonResponse
    {
        $actorName = $request->input('chat_actor_name');

        // Only super_admin can change roles
        if (!$this->chatManager->hasCapability($actorName, 'can_change_roles')) {
            abort(403, trans('chat::messages.forbidden_change_roles'));
        }

        $participant = $room->participants()->findOrFail($participantId);
        $role = $this->roleRepository->findByName($request->input('role_name'));

        if (!$role) {
            abort(422, trans('chat::messages.role_not_found'));
        }

        $participant->update(['role_id' => $role->id]);

        return (new ParticipantResource($participant->fresh(['actor', 'role'])))->response();
    }

    /**
     * Remove a participant from a room.
     */
    public function destroy(Request $request, ChatRoom $room, int $participantId): JsonResponse
    {
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_manage_participants')) {
            abort(403, trans('chat::messages.forbidden_manage_participants'));
        }

        $participant = $room->participants()->findOrFail($participantId);
        $this->roomService->removeParticipant($room, $participant->actor);

        return response()->json(['message' => 'Participant removed.']);
    }
}

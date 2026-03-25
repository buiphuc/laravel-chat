<?php

namespace PhucBui\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\DTOs\RoomData;
use PhucBui\Chat\Http\Requests\StoreRoomRequest;
use PhucBui\Chat\Http\Requests\UpdateRoomRequest;
use PhucBui\Chat\Http\Resources\RoomResource;
use PhucBui\Chat\Services\AdminRoutingService;
use PhucBui\Chat\Services\RoomService;

class RoomController extends Controller
{
    public function __construct(
        protected RoomService $roomService,
        protected AdminRoutingService $adminRoutingService,
        protected ChatManager $chatManager,
    ) {
    }

    /**
     * List rooms for the current actor.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->input('chat_actor');
        $actorName = $request->input('chat_actor_name');
        $perPage = $request->input('per_page', 20);

        if ($this->chatManager->hasCapability($actorName, 'can_see_all_rooms')) {
            $rooms = $this->roomService->getAllRooms($perPage);
        } else {
            $rooms = $this->roomService->getRoomsForActor($actor, $perPage);
        }

        return RoomResource::collection($rooms)->response();
    }

    /**
     * Create a new room.
     */
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $actor = $request->input('chat_actor');
        $actorName = $request->input('chat_actor_name');

        // Direct room
        if ($request->has('target_id')) {
            $targetModel = $request->input('target_type');
            $target = $targetModel::findOrFail($request->input('target_id'));

            // Check if auto-routing should be used
            if (config('chat.auto_routing.enabled') && $actorName === config('chat.auto_routing.from_actor')) {
                $admin = $this->adminRoutingService->findBestAdmin($actor);
                if ($admin) {
                    $target = $admin;
                }
            }

            $room = $this->roomService->findOrCreateDirectRoom($actor, $target);
        } else {
            // Group room
            if (!$this->chatManager->hasCapability($actorName, 'can_create_group')) {
                abort(403, trans('chat::messages.forbidden_create_group'));
            }

            $data = RoomData::fromArray($request->all());
            $room = $this->roomService->createGroupRoom($data, $actor);
        }

        return (new RoomResource($room->load(['participants.actor', 'participants.role'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a room.
     */
    public function update(UpdateRoomRequest $request, $room): JsonResponse
    {
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_manage_participants')) {
            abort(403, trans('chat::messages.forbidden_manage_participants'));
        }

        $room = $this->roomService->updateRoom($room, $request->only(['name', 'metadata']));

        return (new RoomResource($room))->response();
    }

    /**
     * Delete a room.
     */
    public function destroy(Request $request, $room): JsonResponse
    {
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_manage_participants')) {
            abort(403, trans('chat::messages.forbidden_manage_participants'));
        }

        $this->roomService->deleteRoom($room);

        return response()->json(['message' => 'Room deleted.']);
    }
}

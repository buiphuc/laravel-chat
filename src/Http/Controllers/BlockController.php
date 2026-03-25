<?php

namespace PhucBui\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Services\BlockService;

class BlockController extends Controller
{
    public function __construct(
        protected BlockService $blockService,
        protected ChatManager $chatManager,
    ) {
    }

    /**
     * Block a user.
     */
    public function store(Request $request, int $userId): JsonResponse
    {
        $actor = $request->input('chat_actor');
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_block_users') || !config('chat.block_report.block_enabled', true)) {
            abort(403);
        }

        $request->validate([
            'target_type' => 'required|string',
            'reason' => 'sometimes|string|max:500',
        ]);

        $targetModel = $request->input('target_type');
        $target = $targetModel::findOrFail($userId);

        $this->blockService->block($actor, $target, $request->input('reason'));

        return response()->json(['message' => 'User blocked.'], 201);
    }

    /**
     * Unblock a user.
     */
    public function destroy(Request $request, int $userId): JsonResponse
    {
        $actor = $request->input('chat_actor');

        $request->validate([
            'target_type' => 'required|string',
        ]);

        $targetModel = $request->input('target_type');
        $target = $targetModel::findOrFail($userId);

        $this->blockService->unblock($actor, $target);

        return response()->json(['message' => 'User unblocked.']);
    }

    /**
     * Get blocked users list.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->input('chat_actor');

        $blocked = $this->blockService->getBlockedUsers($actor);

        return response()->json(['data' => $blocked]);
    }
}

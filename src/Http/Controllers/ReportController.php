<?php

namespace PhucBui\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PhucBui\Chat\ChatManager;
use PhucBui\Chat\Contracts\Repositories\ChatReportRepositoryInterface;
use PhucBui\Chat\Models\ChatMessage;

class ReportController extends Controller
{
    public function __construct(
        protected ChatReportRepositoryInterface $reportRepository,
        protected ChatManager $chatManager,
    ) {
    }

    /**
     * Report a message.
     */
    public function store(Request $request, int $messageId): JsonResponse
    {
        $actor = $request->input('chat_actor');

        if (!config('chat.block_report.report_enabled', true)) {
            abort(403, 'Report feature is disabled.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $message = ChatMessage::findOrFail($messageId);

        $report = $this->reportRepository->create([
            'message_id' => $message->id,
            'reporter_type' => $actor->getMorphClass(),
            'reporter_id' => $actor->getKey(),
            'reason' => $request->input('reason'),
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Report submitted.', 'data' => $report], 201);
    }

    /**
     * List reports (super_admin / reviewer only).
     */
    public function index(Request $request): JsonResponse
    {
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_review_reports')) {
            abort(403);
        }

        $status = $request->input('status', 'pending');

        if ($status === 'pending') {
            $reports = $this->reportRepository->getPending();
        } else {
            $reports = $this->reportRepository->getAll();
        }

        return response()->json($reports);
    }

    /**
     * Review a report (super_admin / reviewer only).
     */
    public function update(Request $request, int $reportId): JsonResponse
    {
        $actor = $request->input('chat_actor');
        $actorName = $request->input('chat_actor_name');

        if (!$this->chatManager->hasCapability($actorName, 'can_review_reports')) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:reviewed,dismissed',
        ]);

        $report = $this->reportRepository->findOrFail($reportId);

        $this->reportRepository->update($report, [
            'status' => $request->input('status'),
            'reviewer_type' => $actor->getMorphClass(),
            'reviewer_id' => $actor->getKey(),
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Report updated.']);
    }
}

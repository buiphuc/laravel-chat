<?php

namespace PhucBui\Chat\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\Repositories\ChatMessageRepositoryInterface;

class SearchService
{
    public function __construct(
        protected ChatMessageRepositoryInterface $messageRepository,
    ) {
    }

    /**
     * Search messages by keyword with optional filters.
     */
    public function search(
        string $keyword,
        ?int $roomId = null,
        ?Model $sender = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        if (!config('chat.messages.search_enabled', true)) {
            throw new \RuntimeException('Message search is disabled.');
        }

        return $this->messageRepository->search(
            $keyword,
            $roomId,
            $sender,
            $fromDate,
            $toDate,
            $perPage
        );
    }
}

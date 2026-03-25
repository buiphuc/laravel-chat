<?php

namespace PhucBui\Chat\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Models\ChatMessage;

interface ChatMessageRepositoryInterface
{
    public function find(int $id): ?ChatMessage;

    public function findOrFail(int $id): ChatMessage;

    public function create(array $data): ChatMessage;

    public function delete(ChatMessage $message): bool;

    /**
     * Get messages for a room (paginated, newest first).
     */
    public function getByRoom(int $roomId, int $perPage = 50): LengthAwarePaginator;

    /**
     * Search messages by keyword.
     */
    public function search(string $keyword, ?int $roomId = null, ?Model $sender = null, ?string $fromDate = null, ?string $toDate = null, int $perPage = 50): LengthAwarePaginator;
}

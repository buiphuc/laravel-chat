<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\Repositories\ChatMessageRepositoryInterface;
use PhucBui\Chat\Models\ChatMessage;

class ChatMessageRepository extends BaseRepository implements ChatMessageRepositoryInterface
{
    public function __construct(ChatMessage $model)
    {
        parent::__construct($model);
    }

    public function find(int $id): ?ChatMessage
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): ChatMessage
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): ChatMessage
    {
        return $this->model->create($data);
    }

    public function delete(ChatMessage|Model $message): bool
    {
        return $message->delete();
    }

    public function getByRoom(int $roomId, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->where('room_id', $roomId)
            ->with(['sender', 'attachments', 'parent'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function search(
        string $keyword,
        ?int $roomId = null,
        ?Model $sender = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        $query = $this->model->where('body', 'LIKE', "%{$keyword}%");

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        if ($sender) {
            $query->where('sender_type', $sender->getMorphClass())
                  ->where('sender_id', $sender->getKey());
        }

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        return $query
            ->with(['sender', 'room'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}

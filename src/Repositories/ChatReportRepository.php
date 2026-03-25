<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Contracts\Repositories\ChatReportRepositoryInterface;
use PhucBui\Chat\Models\ChatMessageReport;

class ChatReportRepository extends BaseRepository implements ChatReportRepositoryInterface
{
    public function __construct(ChatMessageReport $model)
    {
        parent::__construct($model);
    }

    public function create(array $data): ChatMessageReport
    {
        return $this->model->create($data);
    }

    public function update(ChatMessageReport|\Illuminate\Database\Eloquent\Model $report, array $data): ChatMessageReport
    {
        $report->update($data);
        return $report->fresh();
    }

    public function findOrFail(int $id): ChatMessageReport
    {
        return $this->model->findOrFail($id);
    }

    public function getPending(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->where('status', 'pending')
            ->with(['message.sender', 'reporter'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->with(['message.sender', 'reporter', 'reviewer'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}

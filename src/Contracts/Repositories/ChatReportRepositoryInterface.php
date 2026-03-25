<?php

namespace PhucBui\Chat\Contracts\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use PhucBui\Chat\Models\ChatMessageReport;

interface ChatReportRepositoryInterface
{
    public function create(array $data): ChatMessageReport;

    public function update(ChatMessageReport $report, array $data): ChatMessageReport;

    public function findOrFail(int $id): ChatMessageReport;

    /**
     * Get pending reports (paginated).
     */
    public function getPending(int $perPage = 20): LengthAwarePaginator;

    /**
     * Get all reports (paginated).
     */
    public function getAll(int $perPage = 20): LengthAwarePaginator;
}

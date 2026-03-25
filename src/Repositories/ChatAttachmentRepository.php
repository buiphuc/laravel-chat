<?php

namespace PhucBui\Chat\Repositories;

use Illuminate\Support\Collection;
use PhucBui\Chat\Contracts\Repositories\ChatAttachmentRepositoryInterface;
use PhucBui\Chat\Models\ChatAttachment;

class ChatAttachmentRepository extends BaseRepository implements ChatAttachmentRepositoryInterface
{
    public function __construct(ChatAttachment $model)
    {
        parent::__construct($model);
    }

    public function create(array $data): ChatAttachment
    {
        return $this->model->create($data);
    }

    public function delete(ChatAttachment|\Illuminate\Database\Eloquent\Model $attachment): bool
    {
        return $attachment->delete();
    }

    public function getByMessage(int $messageId): Collection
    {
        return $this->model->where('message_id', $messageId)->get();
    }
}

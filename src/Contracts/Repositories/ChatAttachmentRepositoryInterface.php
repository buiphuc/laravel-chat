<?php

namespace PhucBui\Chat\Contracts\Repositories;

use Illuminate\Support\Collection;
use PhucBui\Chat\Models\ChatAttachment;

interface ChatAttachmentRepositoryInterface
{
    public function create(array $data): ChatAttachment;

    public function delete(ChatAttachment $attachment): bool;

    /**
     * Get attachments for a message.
     */
    public function getByMessage(int $messageId): Collection;
}

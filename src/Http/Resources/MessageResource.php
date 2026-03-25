<?php

namespace PhucBui\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_id' => $this->room_id,
            'sender_type' => $this->sender_type,
            'sender_id' => $this->sender_id,
            'type' => $this->type,
            'body' => $this->body,
            'metadata' => $this->metadata,
            'parent_id' => $this->parent_id,
            'is_reply' => $this->is_reply,
            'created_at' => $this->created_at?->toISOString(),
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->getKey(),
                'type' => $this->sender->getMorphClass(),
                'name' => $this->sender->getChatDisplayName(),
                'avatar' => $this->sender->getChatAvatar(),
            ]),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'file_name' => $a->file_name,
                'file_type' => $a->file_type,
                'file_size' => $a->file_size,
                'file_size_human' => $a->file_size_human,
            ])),
            'parent' => new MessageResource($this->whenLoaded('parent')),
        ];
    }
}

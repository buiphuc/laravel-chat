<?php

namespace PhucBui\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PhucBui\Chat\ChatManager;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_members' => $this->max_members,
            'is_direct' => $this->is_direct,
            'is_group' => $this->is_group,
            'metadata' => $this->metadata,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'unread_count' => $this->when(
                $request->input('chat_actor'),
                fn () => $this->getUnreadCountFor($request->input('chat_actor'))
            ),
        ];
    }
}

<?php

namespace PhucBui\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor_type' => $this->actor_type,
            'actor_id' => $this->actor_id,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'display_name' => $this->role->display_name,
            ]),
            'joined_at' => $this->joined_at?->toISOString(),
            'last_read_at' => $this->last_read_at?->toISOString(),
            'is_muted' => $this->is_muted,
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->getKey(),
                'type' => $this->actor->getMorphClass(),
                'name' => $this->actor->getChatDisplayName(),
                'avatar' => $this->actor->getChatAvatar(),
            ]),
        ];
    }
}

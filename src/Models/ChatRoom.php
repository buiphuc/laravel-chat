<?php

namespace PhucBui\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatRoom extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'max_members' => 'integer',
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.rooms', 'chat_rooms');
    }

    /**
     * The creator of this room (polymorphic).
     */
    public function creator(): MorphTo
    {
        return $this->morphTo('created_by');
    }

    /**
     * Participants in this room.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class, 'room_id');
    }

    /**
     * Messages in this room.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }

    /**
     * Check if this room is a direct (1v1) room.
     */
    public function getIsDirectAttribute(): bool
    {
        return $this->max_members === 2;
    }

    /**
     * Check if this room is a group room.
     */
    public function getIsGroupAttribute(): bool
    {
        return $this->max_members !== 2;
    }

    /**
     * Get the latest message.
     */
    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'room_id')->latestOfMany();
    }

    /**
     * Get unread count for a specific actor.
     */
    public function getUnreadCountFor(Model $actor): int
    {
        $participant = $this->participants()
            ->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey())
            ->first();

        if (!$participant || !$participant->last_read_at) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('created_at', '>', $participant->last_read_at)
            ->where(function ($query) use ($actor) {
                $query->where('sender_type', '!=', $actor->getMorphClass())
                      ->orWhere('sender_id', '!=', $actor->getKey());
            })
            ->count();
    }
}

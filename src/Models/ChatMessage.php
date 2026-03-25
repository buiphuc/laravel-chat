<?php

namespace PhucBui\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.messages', 'chat_messages');
    }

    /**
     * The sender (polymorphic).
     */
    public function sender(): MorphTo
    {
        return $this->morphTo('sender');
    }

    /**
     * The room this message belongs to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    /**
     * Parent message (for replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Reply messages.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Attachments for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ChatAttachment::class, 'message_id');
    }

    /**
     * Reports for this message.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ChatMessageReport::class, 'message_id');
    }

    /**
     * Check if this message is a reply.
     */
    public function getIsReplyAttribute(): bool
    {
        return $this->parent_id !== null;
    }
}

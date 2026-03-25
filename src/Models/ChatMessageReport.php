<?php

namespace PhucBui\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatMessageReport extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.message_reports', 'chat_message_reports');
    }

    /**
     * The reported message.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    /**
     * The actor who reported.
     */
    public function reporter(): MorphTo
    {
        return $this->morphTo('reporter');
    }

    /**
     * The admin/super_admin who reviewed.
     */
    public function reviewer(): MorphTo
    {
        return $this->morphTo('reviewer');
    }

    /**
     * Check if report is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }
}

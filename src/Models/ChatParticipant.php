<?php

namespace PhucBui\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatParticipant extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.participants', 'chat_participants');
    }

    /**
     * The actor (User/Customer/Admin) participating.
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor');
    }

    /**
     * The room this participant belongs to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    /**
     * The role of this participant in the room.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ChatRole::class, 'role_id');
    }

    /**
     * Check if participant has a specific permission via their role.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }
}

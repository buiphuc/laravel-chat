<?php

namespace PhucBui\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRole extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'permissions' => 'array',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.roles', 'chat_roles');
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Participants with this role.
     */
    public function participants()
    {
        return $this->hasMany(ChatParticipant::class, 'role_id');
    }
}

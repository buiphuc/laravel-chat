<?php

namespace PhucBui\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatBlockedUser extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.blocked_users', 'chat_blocked_users');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /**
     * The user who blocked.
     */
    public function blocker(): MorphTo
    {
        return $this->morphTo('blocker');
    }

    /**
     * The user who was blocked.
     */
    public function blocked(): MorphTo
    {
        return $this->morphTo('blocked');
    }
}

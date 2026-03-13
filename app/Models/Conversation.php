<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_agent_id',
        'status',
        'channel',
        'last_message_at',
        'last_assigned_call',
        'last_assigned_at',
        'last_closed_at',
        'last_open_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_assigned_call' => 'datetime',
        'last_assigned_at' => 'datetime',
        'last_closed_at' => 'datetime',
        'last_open_at' => 'datetime',
        'closed_at' => 'datetime',
        'status' => ConversationStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }
}

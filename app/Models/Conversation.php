<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $assigned_agent_id
 * @property ConversationStatus $status
 * @property string|null $channel
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $last_assign_request
 * @property \Illuminate\Support\Carbon|null $last_assigned_at
 * @property \Illuminate\Support\Carbon|null $last_closed_at
 * @property \Illuminate\Support\Carbon|null $last_opened_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $assignedAgent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ConversationMessage> $messages
 */
class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_agent_id',
        'status',
        'channel',
        'last_message_at',
        'last_assign_request',
        'last_assigned_at',
        'last_closed_at',
        'last_opened_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_assign_request' => 'datetime',
        'last_assigned_at' => 'datetime',
        'last_closed_at' => 'datetime',
        'last_opened_at' => 'datetime',
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

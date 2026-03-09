<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationEscalation extends Model
{
    protected $fillable = [
        'conversation_id',
        'requested_by_message_id',
        'requested_at',
        'assigned_agent_id',
        'first_agent_response_at',
        'resolved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'first_agent_response_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function requestedByMessage(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class, 'requested_by_message_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }
}

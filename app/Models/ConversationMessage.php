<?php

namespace App\Models;

use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_user_id',
        'message_type',
        'language',
        'message_text',
        'answer_code',
    ];

    protected $casts = [
        'sender_type' => ConversationMessageSenderType::class,
        'message_type' => ConversationMessageType::class,
        'language' => Language::class,
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}

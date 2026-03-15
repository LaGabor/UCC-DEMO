<?php

namespace App\Models;

use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $conversation_id
 * @property ConversationMessageSenderType $sender_type
 * @property int|null $sender_user_id
 * @property ConversationMessageType $message_type
 * @property Language|null $language
 * @property string|null $message_text
 * @property string|null $answer_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Conversation $conversation
 * @property-read User|null $senderUser
 */
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

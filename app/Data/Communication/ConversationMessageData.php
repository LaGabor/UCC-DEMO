<?php

namespace App\Data\Communication;

use App\Models\ConversationMessage;

readonly class ConversationMessageData
{
    public function __construct(
        public int     $id,
        public string  $senderType,
        public string  $messageType,
        public ?string $messageText,
        public ?int    $senderUserId,
        public string  $createdAt,
    ) {
    }

    public static function fromModel(ConversationMessage $message): self
    {
        return new self(
            id: $message->id,
            senderType: $message->sender_type->value,
            messageType: $message->message_type->value,
            messageText: $message->message_text,
            senderUserId: $message->sender_user_id,
            createdAt: (string) $message->created_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sender_type' => $this->senderType,
            'message_type' => $this->messageType,
            'message_text' => $this->messageText,
            'sender_user_id' => $this->senderUserId,
            'created_at' => $this->createdAt,
        ];
    }
}

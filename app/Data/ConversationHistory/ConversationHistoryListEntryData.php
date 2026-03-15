<?php

namespace App\Data\ConversationHistory;

readonly class ConversationHistoryListEntryData
{
    public function __construct(
        public int $conversation_id,
        public int $user_id,
        public string $user_name,
        public ?string $last_message_at,
        public ?string $last_message_text,
    ) {
    }

    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'last_message_at' => $this->last_message_at,
            'last_message_text' => $this->last_message_text,
        ];
    }
}

<?php

namespace App\Data\Communication;

use App\Enums\ConversationStatus;

readonly class UserMessageAcceptedData
{
    public function __construct(
        public int $conversationId,
        public ConversationStatus $status,
        public string $message = 'Message delivered to the system.',
    ) {
    }

    /**
     * @return array{conversation_id:int,status:string,message:string}
     */
    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}

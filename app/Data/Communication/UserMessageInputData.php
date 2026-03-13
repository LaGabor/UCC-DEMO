<?php

namespace App\Data\Communication;

use App\Enums\ConversationStatus;
use App\Http\Requests\API\Communication\SendUserMessageRequest;

readonly class UserMessageInputData
{
    public function __construct(
        public ?int               $conversationId,
        public string             $messageText,
        public ConversationStatus $conversationStatus,
    ) {
    }

    public static function fromRequest(SendUserMessageRequest $request): self
    {
        return new self(
            conversationId: $request->integer('conversation_id') ?: null,
            messageText: $request->string('message_text')->toString(),
            conversationStatus: ConversationStatus::from(
                $request->string('conversation_status')->toString()
            ),
        );
    }
}

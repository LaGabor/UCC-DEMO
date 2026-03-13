<?php

namespace App\Data\Communication;

use App\Http\Requests\API\Communication\UpdateConversationStatusRequest;

class UpdateConversationStatusData
{
    public function __construct(
        public readonly ?int $conversationId,
    ) {
    }

    public static function fromRequest(UpdateConversationStatusRequest $request): self
    {
        return new self(
            conversationId: $request->integer('conversation_id') ?: null,
        );
    }
}

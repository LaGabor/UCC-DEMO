<?php

namespace App\Data\Communication;

use App\Http\Requests\API\Communication\CloseUserCommunicationRequest;

readonly class CloseUserCommunicationInputData
{
    public function __construct(
        public ?int $conversationId,
    ) {
    }

    public static function fromRequest(CloseUserCommunicationRequest $request): self
    {
        return new self(
            conversationId: $request->integer('conversation_id') ?: null,
        );
    }
}

<?php

namespace App\Data\Communication;

use App\Models\Conversation;
use Illuminate\Support\Collection;

class UserConversationResponseData
{
    public function __construct(
        public readonly ?int $conversationId,
        public readonly ?string $status,
        public readonly array $messages,
    ) {
    }

    public static function fromConversationAndMessages(?Conversation $conversation, Collection $messages): self
    {
        $items = $messages
            ->map(static fn ($message) => ConversationMessageData::fromModel($message))
            ->all();

        return new self(
            conversationId: $conversation?->id,
            status: $conversation?->status?->value,
            messages: $items,
        );
    }

    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'status' => $this->status,
            'messages' => array_map(
                static fn (ConversationMessageData $message) => $message->toArray(),
                $this->messages
            ),
        ];
    }
}

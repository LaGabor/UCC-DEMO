<?php

namespace App\Data\AgentMonitor;

use App\Data\Communication\ConversationMessageData;
use Illuminate\Support\Collection;

class AgentMonitorConversationHistoryData
{
    public function __construct(
        public readonly array $messages,
    ) {
    }

    public static function fromMessages(Collection $messages): self
    {
        $items = $messages
            ->map(fn ($message) => ConversationMessageData::fromModel($message)->toArray())
            ->values()
            ->all();

        return new self(messages: $items);
    }

    public function toArray(): array
    {
        return [
            'messages' => $this->messages,
        ];
    }
}

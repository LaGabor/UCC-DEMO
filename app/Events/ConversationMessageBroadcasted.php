<?php

namespace App\Events;

use App\Data\Communication\ConversationMessageData;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationMessageBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly ConversationMessage $message,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.message.created';
    }

    public function broadcastWith(): array
    {

        return [
            'conversation_id' => $this->conversation->id,
            'status' => $this->conversation->status->value,
            'message' => ConversationMessageData::fromModel($this->message)->toArray(),
        ];
    }
}

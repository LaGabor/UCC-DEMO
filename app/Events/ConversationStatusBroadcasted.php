<?php

namespace App\Events;

use App\Enums\ConversationBroadcastType;
use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationStatusBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
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
        return 'conversation.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => ConversationBroadcastType::STATUS_CHANGE->value,
            'conversation_id' => $this->conversation->id,
            'status' => $this->conversation->status->value,
        ];
    }
}

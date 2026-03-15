<?php

namespace App\Events;

use App\Enums\ConversationBroadcastType;
use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AgentMonitorConversationBatchBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Collection $conversations,
    ) {
        $this->conversations->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agent-monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.status.batch.updated';
    }

    public function broadcastWith(): array
    {
        $items = $this->conversations->map(function (Conversation $conversation) {
            $user = $conversation->user;

            return [
                'conversation_id' => $conversation->id,
                'user_id' => $conversation->user_id,
                'user_name' => $user?->name ?? '',
                'assigned_agent_id' => $conversation->assigned_agent_id,
                'status' => $conversation->status->value,
                'created_at' => $conversation->created_at->toIso8601String(),
                'last_assign_request' => $conversation->last_assign_request?->toIso8601String(),
                'last_assigned_at' => $conversation->last_assigned_at?->toIso8601String(),
                'last_closed_at' => $conversation->last_closed_at?->toIso8601String(),
                'last_open_at' => $conversation->last_opened_at?->toIso8601String(),
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            ];
        })->values()->all();

        return [
            'type' => ConversationBroadcastType::STATUS_CHANGE_OBJECT->value,
            'conversations' => $items,
        ];
    }
}

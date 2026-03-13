<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentMonitorConversationBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
    ) {
        $this->conversation->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agent-monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.status.updated';
    }

    public function broadcastWith(): array
    {
        $user = $this->conversation->user;

        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->conversation->user_id,
            'user_name' => $user?->name ?? '',
            'assigned_agent_id' => $this->conversation->assigned_agent_id,
            'status' => $this->conversation->status->value,
            'created_at' => $this->conversation->created_at->toIso8601String(),
            'last_assigned_call' => $this->conversation->last_assigned_call?->toIso8601String(),
            'last_assigned_at' => $this->conversation->last_assigned_at?->toIso8601String(),
            'last_closed_at' => $this->conversation->last_closed_at?->toIso8601String(),
            'last_open_at' => $this->conversation->last_open_at?->toIso8601String(),
        ];
    }
}

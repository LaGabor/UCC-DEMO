<?php

namespace App\Data\AgentMonitor;

use App\Models\Conversation;

class AgentMonitorConversationData
{
    public function __construct(
        public readonly int $conversationId,
        public readonly int $userId,
        public readonly string $userName,
        public readonly ?int $assignedAgentId,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $lastAssignedCall,
        public readonly ?string $lastAssignedAt,
        public readonly ?string $lastClosedAt,
        public readonly ?string $lastOpenAt,
    ) {
    }

    public static function fromConversation(Conversation $conversation): self
    {
        $conversation->loadMissing('user');
        $user = $conversation->user;

        return new self(
            conversationId: $conversation->id,
            userId: $conversation->user_id,
            userName: $user?->name ?? '',
            assignedAgentId: $conversation->assigned_agent_id,
            status: $conversation->status->value,
            createdAt: $conversation->created_at->toIso8601String(),
            lastAssignedCall: $conversation->last_assigned_call?->toIso8601String(),
            lastAssignedAt: $conversation->last_assigned_at?->toIso8601String(),
            lastClosedAt: $conversation->last_closed_at?->toIso8601String(),
            lastOpenAt: $conversation->last_open_at?->toIso8601String(),
        );
    }

    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'assigned_agent_id' => $this->assignedAgentId,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'last_assigned_call' => $this->lastAssignedCall,
            'last_assigned_at' => $this->lastAssignedAt,
            'last_closed_at' => $this->lastClosedAt,
            'last_open_at' => $this->lastOpenAt,
        ];
    }
}

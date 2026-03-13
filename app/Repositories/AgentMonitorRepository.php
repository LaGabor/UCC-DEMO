<?php

namespace App\Repositories;

use App\Contracts\Repositories\AgentMonitorRepositoryInterface;
use App\Data\AgentMonitor\AgentMonitorConversationData;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use Illuminate\Support\Collection;

class AgentMonitorRepository implements AgentMonitorRepositoryInterface
{
    public function getNonClosedConversations(): Collection
    {
        return Conversation::query()
            ->with('user')
            ->where('status', '!=', ConversationStatus::CLOSED)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Conversation $c) => AgentMonitorConversationData::fromConversation($c));
    }
}

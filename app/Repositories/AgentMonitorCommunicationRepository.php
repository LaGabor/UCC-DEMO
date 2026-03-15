<?php

namespace App\Repositories;

use App\Contracts\Repositories\AgentMonitorCommunicationRepositoryInterface;
use App\Models\ConversationMessage;
use Illuminate\Support\Collection;

class AgentMonitorCommunicationRepository implements AgentMonitorCommunicationRepositoryInterface
{
    public function getConversationMessages(int $conversationId, int $limit = 100): Collection
    {
        return ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }
}

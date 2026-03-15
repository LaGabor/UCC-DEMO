<?php

namespace App\Contracts\Repositories;

use App\Models\ConversationMessage;
use Illuminate\Support\Collection;

interface AgentMonitorCommunicationRepositoryInterface
{
    public function getConversationMessages(int $conversationId, int $limit = 100): Collection;
}

<?php

namespace App\Contracts\Services;

use App\Data\AgentMonitor\AgentMonitorConversationHistoryData;
use App\Data\ConversationHistory\ConversationHistoryListEntryData;
use Illuminate\Support\Collection;

interface ConversationHistoryServiceInterface
{

    public function getConversationList(int $requestingUserId): Collection;

    public function getFullConversationHistory(int $requestingUserId, int $conversationId): AgentMonitorConversationHistoryData;
}

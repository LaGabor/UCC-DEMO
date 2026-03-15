<?php

namespace App\Contracts\Services;

use App\Data\AgentMonitor\AgentMonitorConversationHistoryData;

interface AgentMonitorCommunicationServiceInterface
{
    public function viewUserChatHistory(int $requestingUserId, int $conversationId): AgentMonitorConversationHistoryData;

    public function answerUserChatHistory(int $requestingUserId, int $conversationId): AgentMonitorConversationHistoryData;

    public function closeAssigned(int $requestingUserId, int $conversationId): void;

    public function closeWaitingHuman(int $requestingUserId, int $conversationId): void;

    public function closeAgentCommunicationStatus(int $requestingUserId): void;

    public function sendAgentMessage(int $requestingUserId, int $conversationId, string $messageText): void;
}

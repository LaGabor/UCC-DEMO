<?php

namespace App\Services;

use App\Contracts\Repositories\ConversationHistoryRepositoryInterface;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\ConversationHistoryServiceInterface;
use App\Data\AgentMonitor\AgentMonitorConversationHistoryData;
use App\Data\Communication\ConversationMessageData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Exceptions\ApiDomainException;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Collection;

class ConversationHistoryService implements ConversationHistoryServiceInterface
{
    public function __construct(
        private readonly ConversationHistoryRepositoryInterface $conversationHistoryRepository,
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
    ) {
    }

    public function getConversationList(int $requestingUserId): Collection
    {
        $this->ensureAdminOrHelpdeskAgent($requestingUserId);

        return $this->conversationHistoryRepository->getAllForList();
    }

    public function getFullConversationHistory(int $requestingUserId, int $conversationId): AgentMonitorConversationHistoryData
    {
        $this->ensureAdminOrHelpdeskAgent($requestingUserId);

        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);
        if (! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_NOT_FOUND,
                'Conversation not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $messages = $this->conversationHistoryRepository->getFullMessageHistory($conversationId);

        $messageDataItems = $messages
            ->map(fn ($message) => ConversationMessageData::fromModel($message)->toArray())
            ->values()
            ->all();

        return new AgentMonitorConversationHistoryData(messages: $messageDataItems);
    }

    private function ensureAdminOrHelpdeskAgent(int $requestingUserId): void
    {
        $user = User::query()->find($requestingUserId);
        if (! $user) {
            throw new ApiDomainException(
                ApiDomainErrorCode::USER_NOT_FOUND,
                'User not found.',
                null,
                ApiDomainStatus::UNAUTHORIZED
            );
        }

        if (! in_array($user->role, [UserRole::ADMIN, UserRole::HELPDESK_AGENT], true)) {
            throw new ApiDomainException(
                ApiDomainErrorCode::AGENT_MONITOR_ACCESS_DENIED,
                'Only admin or helpdesk agent can access conversation history.',
                null,
                ApiDomainStatus::FORBIDDEN
            );
        }
    }
}

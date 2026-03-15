<?php

namespace App\Services;

use App\Contracts\Repositories\AgentMonitorCommunicationRepositoryInterface;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\AgentMonitorCommunicationServiceInterface;
use App\Data\AgentMonitor\AgentMonitorConversationHistoryData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiDomainException;
use App\Models\ConversationMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentMonitorCommunicationService implements AgentMonitorCommunicationServiceInterface
{
    private const HISTORY_LIMIT = 100;

    public function __construct(
        private readonly AgentMonitorCommunicationRepositoryInterface $agentMonitorCommunicationRepository,
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
    ) {
    }

    /**
     * @throws ApiDomainException
     */
    public function viewUserChatHistory(int $requestingUserId, int $conversationId): AgentMonitorConversationHistoryData
    {
        $this->ensureAgentMonitorAccess($requestingUserId);

        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);
        if (! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_NOT_FOUND,
                'Conversation not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $messages = $this->agentMonitorCommunicationRepository->getConversationMessages(
            $conversationId,
            self::HISTORY_LIMIT
        );

        return AgentMonitorConversationHistoryData::fromMessages($messages);
    }

    /**
     * @throws ApiDomainException
     */
    public function answerUserChatHistory(int $requestingUserId, int $conversationId): AgentMonitorConversationHistoryData
    {
        $this->ensureAgentMonitorAccess($requestingUserId);

        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);
        if (! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_NOT_FOUND,
                'Conversation not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $messages = $this->agentMonitorCommunicationRepository->getConversationMessages(
            $conversationId,
            self::HISTORY_LIMIT
        );

        if ($conversation->status !== ConversationStatus::WAITING_HUMAN) {
            return AgentMonitorConversationHistoryData::fromMessages($messages);
        }

        $conversation->assigned_agent_id = $requestingUserId;
        $this->userCommunicationRepository->updateConversationStatus(
            $conversation,
            ConversationStatus::ASSIGNED
        );

        return AgentMonitorConversationHistoryData::fromMessages($messages);
    }

    /**
     * @throws ApiDomainException
     */
    public function closeAssigned(int $requestingUserId, int $conversationId): void
    {
        $this->ensureAgentMonitorAccess($requestingUserId);

        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);
        if (! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_NOT_FOUND,
                'Conversation not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $this->userCommunicationRepository->updateConversationStatus(
            $conversation,
            ConversationStatus::WAITING_HUMAN
        );
    }

    /**
     * @throws ApiDomainException
     */
    public function closeWaitingHuman(int $requestingUserId, int $conversationId): void
    {
        $this->ensureAgentMonitorAccess($requestingUserId);

        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);
        if (! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_NOT_FOUND,
                'Conversation not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $this->userCommunicationRepository->updateConversationStatus(
            $conversation,
            ConversationStatus::OPEN
        );
    }

    /**
     * @throws ApiDomainException
     */
    public function closeAgentCommunicationStatus(int $requestingUserId): void
    {
        $this->ensureAgentMonitorAccess($requestingUserId);

        $conversations = $this->userCommunicationRepository->findConversationsAssignedToAgent($requestingUserId);

        foreach ($conversations as $conversation) {
            $this->userCommunicationRepository->updateConversationStatus(
                $conversation,
                ConversationStatus::WAITING_HUMAN
            );
        }
    }

    /**
     * @throws ApiDomainException
     */
    public function sendAgentMessage(int $requestingUserId, int $conversationId, string $messageText): void
    {
        $this->ensureAgentMonitorAccess($requestingUserId);

        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);
        if (! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_NOT_FOUND,
                'Conversation not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $message = $this->createMessageOrFail(
            $conversationId,
            ConversationMessageSenderType::AGENT,
            ConversationMessageType::AGENT_ANSWER,
            $requestingUserId,
            $messageText
        );

        $this->userCommunicationRepository->touchConversationLastMessageAt(
            $conversation,
            CarbonImmutable::now()
        );
    }

    /**
     * @throws ApiDomainException
     */
    private function ensureAgentMonitorAccess(int $requestingUserId): void
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
                'Only admin or helpdesk agent can perform this action.',
                null,
                ApiDomainStatus::FORBIDDEN
            );
        }
    }

    /**
     * @throws ApiDomainException
     */
    private function createMessageOrFail(
        int $conversationId,
        ConversationMessageSenderType $senderType,
        ConversationMessageType $messageType,
        ?int $senderUserId,
        ?string $messageText
    ): ConversationMessage {
        try {
            return $this->userCommunicationRepository->createMessage(
                $conversationId,
                $senderType,
                $messageType,
                $senderUserId,
                $messageText
            );
        } catch (Throwable $e) {
            Log::error('conversation.message_create_failed', [
                'conversation_id' => $conversationId,
                'sender_type' => $senderType->value,
                'message_type' => $messageType->value,
                'error' => $e->getMessage(),
            ]);
            throw new ApiDomainException(
                ApiDomainErrorCode::INTERNAL_SERVER_ERROR,
                'Failed to create conversation message.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }
    }
}

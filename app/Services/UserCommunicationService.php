<?php

namespace App\Services;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\SystemMessageBotServiceInterface;
use App\Contracts\Services\UserCommunicationServiceInterface;
use App\Data\Communication\UpdateConversationStatusData;
use App\Data\Communication\UserMessageAcceptedData;
use App\Data\Communication\UserMessageInputData;
use App\Data\Communication\UserConversationResponseData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Enums\MessageResponseSetting;
use App\Enums\MessageStrength;
use App\Events\AgentMonitorConversationBroadcasted;
use App\Events\ConversationMessageBroadcasted;
use App\Events\ConversationStatusBroadcasted;
use App\Exceptions\ApiDomainException;
use App\Jobs\RouteConversationMessageJob;
use App\Models\Conversation;
use Carbon\CarbonImmutable;

class UserCommunicationService implements UserCommunicationServiceInterface
{
    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
        private readonly SystemMessageBotServiceInterface $systemMessageBotService,
    ) {
    }

    private const LAST_MESSAGES_LIMIT = 100;

    public function getUserConversation(int $userId): UserConversationResponseData
    {
        $conversation = $this->userCommunicationRepository->findLatestConversationByUserId($userId);
        if (! $conversation) {
            return UserConversationResponseData::fromConversationAndMessages(null, collect());
        }

        $messages = $this->userCommunicationRepository->listMessagesForConversation(
            $conversation->id,
            self::LAST_MESSAGES_LIMIT
        );

        return UserConversationResponseData::fromConversationAndMessages($conversation, $messages);
    }

    /**
     * @throws ApiDomainException
     */
    public function sendUserMessage(int $userId, UserMessageInputData $data): UserMessageAcceptedData
    {
        $conversation = $this->resolveConversationForUserMessage(
            $userId,
            $data->conversationId,
            $data->conversationStatus
        );

        $userMessage = $this->userCommunicationRepository->createMessage(
            $conversation->id,
            ConversationMessageSenderType::USER,
            ConversationMessageType::QUESTION,
            $userId,
            $data->messageText
        );

        $this->userCommunicationRepository->touchConversationLastMessageAt($conversation, CarbonImmutable::now());
        $conversation = $conversation->refresh();

        broadcast(new ConversationMessageBroadcasted($conversation, $userMessage));

        $setting = MessageResponseSetting::tryFrom(
            (string) config('communication.message_response_setting', MessageResponseSetting::HYBRID->value)
        ) ?? MessageResponseSetting::HYBRID;

        if ($conversation->status === ConversationStatus::ASSIGNED) {
            return new UserMessageAcceptedData(
                conversationId: $conversation->id,
                status: $conversation->status
            );
        }

        if ($setting === MessageResponseSetting::HYBRID || $setting === MessageResponseSetting::SYSTEM_BOT) {
            $systemDecision = $this->systemMessageBotService->resolve($conversation, $userMessage);
            if (
                $systemDecision->strength->value > MessageStrength::VERY_WEAK->value
                && $systemDecision->message !== null
            ) {

                $botMessage = $this->userCommunicationRepository->createMessage(
                    $conversation->id,
                    ConversationMessageSenderType::BOT,
                    ConversationMessageType::BOT_ANSWER,
                    null,
                    $systemDecision->message
                );
                $this->userCommunicationRepository->touchConversationLastMessageAt($conversation, CarbonImmutable::now());
                broadcast(new ConversationMessageBroadcasted($conversation->refresh(), $botMessage));

                return new UserMessageAcceptedData(
                    conversationId: $conversation->id,
                    status: $conversation->status
                );
            }
            if ($setting === MessageResponseSetting::SYSTEM_BOT) {
                $noAnswerMessage = $this->userCommunicationRepository->createMessage(
                    $conversation->id,
                    ConversationMessageSenderType::SYSTEM,
                    ConversationMessageType::SYSTEM_NOTICE,
                    null,
                    'No predefined answer for this question.'
                );
                $this->userCommunicationRepository->touchConversationLastMessageAt($conversation, CarbonImmutable::now());
                broadcast(new ConversationMessageBroadcasted($conversation->refresh(), $noAnswerMessage));

                return new UserMessageAcceptedData(
                    conversationId: $conversation->id,
                    status: $conversation->status
                );
            }
        }

        RouteConversationMessageJob::dispatch($conversation->id, $userMessage->id);

        return new UserMessageAcceptedData(
            conversationId: $conversation->id,
            status: $conversation->status
        );
    }

    /**
     * @throws ApiDomainException
     */
    public function callAgent(int $userId, UpdateConversationStatusData $data): UserConversationResponseData
    {
        $conversation = $this->resolveConversationForStatusChange(
            $userId,
            $data->conversationId,
            ConversationStatus::WAITING_HUMAN
        );

        $messages = $this->userCommunicationRepository->listMessagesForConversation($conversation->id);

        return UserConversationResponseData::fromConversationAndMessages($conversation, $messages);
    }

    /**
     * @throws ApiDomainException
     */
    public function cancelCall(int $userId, UpdateConversationStatusData $data): UserConversationResponseData
    {
        $conversation = $this->resolveConversationForStatusChange(
            $userId,
            $data->conversationId,
            ConversationStatus::OPEN
        );

        $messages = $this->userCommunicationRepository->listMessagesForConversation($conversation->id);

        return UserConversationResponseData::fromConversationAndMessages($conversation, $messages);
    }

    /**
     * @throws ApiDomainException
     */
    private function resolveConversationForUserMessage(
        int $userId,
        ?int $conversationId,
        ConversationStatus $conversationStatus
    ): Conversation {
        if ($conversationStatus === ConversationStatus::CLOSED) {
            $conversationStatus = ConversationStatus::OPEN;
        }

        if ($conversationId !== null) {
            $conversation = $this->userCommunicationRepository->findConversationByIdAndUserId($conversationId, $userId);
            if (! $conversation) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::CONVERSATION_ACCESS_DENIED,
                    'Conversation access denied.',
                    null,
                    ApiDomainStatus::NOT_FOUND
                );
            }

            if ($conversation->status !== $conversationStatus) {
                $conversation = $this->userCommunicationRepository->updateConversationStatus(
                    $conversation,
                    $conversationStatus
                );
                $this->broadcastConversationStatus($conversation);
            }

            return $conversation;
        }

        $latestConversation = $this->userCommunicationRepository->findLatestConversationByUserId($userId);
        if (! $latestConversation) {
            $conversation = $this->userCommunicationRepository->createConversation($userId, $conversationStatus);
            $this->broadcastConversationStatus($conversation);

            return $conversation;
        }

        if ($latestConversation->status !== $conversationStatus) {
            $latestConversation = $this->userCommunicationRepository->updateConversationStatus(
                $latestConversation,
                $conversationStatus
            );
            $this->broadcastConversationStatus($latestConversation);
        }

        return $latestConversation;
    }

    /**
     * @throws ApiDomainException
     */
    private function resolveConversationForStatusChange(
        int $userId,
        ?int $conversationId,
        ConversationStatus $targetStatus
    ): Conversation {
        $conversation = $conversationId !== null
            ? $this->userCommunicationRepository->findConversationByIdAndUserId($conversationId, $userId)
            : $this->userCommunicationRepository->findLatestConversationByUserId($userId);

        if ($conversationId !== null && ! $conversation) {
            throw new ApiDomainException(
                ApiDomainErrorCode::CONVERSATION_ACCESS_DENIED,
                'Conversation access denied.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        if (! $conversation) {
            $conversation = $this->userCommunicationRepository->createConversation($userId, $targetStatus);
        } else {
            if ($targetStatus === ConversationStatus::WAITING_HUMAN) {
                $this->userCommunicationRepository->touchConversationLastAssignedCallIfNull($conversation);
                $conversation = $conversation->refresh();
            }
            $conversation = $this->userCommunicationRepository->updateConversationStatus($conversation, $targetStatus);
        }

        $this->broadcastConversationStatus($conversation);

        return $conversation;
    }

    private function broadcastConversationStatus(Conversation $conversation): void
    {
        try {
            broadcast(new ConversationStatusBroadcasted($conversation));
            broadcast(new AgentMonitorConversationBroadcasted($conversation));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

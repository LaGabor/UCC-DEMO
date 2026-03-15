<?php

namespace App\Services;

use App\Actions\BotAnswerRouter;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\UserCommunicationServiceInterface;
use App\Data\Communication\CloseUserCommunicationInputData;
use App\Data\Communication\CloseUserCommunicationResultData;
use App\Data\Communication\UpdateConversationStatusData;
use App\Data\Communication\UserMessageAcceptedData;
use App\Data\Communication\UserMessageInputData;
use App\Data\Communication\UserConversationResponseData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Exceptions\ApiDomainException;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserCommunicationService implements UserCommunicationServiceInterface
{
    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
        private readonly BotAnswerRouter $botAnswerRouter,
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
    public function createUserMessage(int $userId, UserMessageInputData $data): UserMessageAcceptedData
    {
        $conversation = $this->resolveConversationForUserMessage(
            $userId,
            $data->conversationId,
            $data->conversationStatus
        );

        try {
            $userMessage = $this->userCommunicationRepository->createMessage(
                $conversation->id,
                ConversationMessageSenderType::USER,
                ConversationMessageType::QUESTION,
                $userId,
                $data->messageText
            );
            $this->userCommunicationRepository->touchConversationLastMessageAt(
                $conversation,
                CarbonImmutable::now()
            );
        } catch (Throwable $e) {
            Log::error('conversation.message_create_failed', [
                'conversation_id' => $conversation->id,
                'sender_type' => ConversationMessageSenderType::USER->value,
                'message_type' => ConversationMessageType::QUESTION->value,
                'error' => $e->getMessage(),
            ]);
            report($e);
            throw new ApiDomainException(
                ApiDomainErrorCode::INTERNAL_SERVER_ERROR,
                'Failed to create conversation message.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }

        $conversation = $conversation->refresh();

        return ($this->botAnswerRouter)($conversation, $userMessage);
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

    public function closeUserCommunication(int $userId, CloseUserCommunicationInputData $data): CloseUserCommunicationResultData
    {
        $closed = [];

        if ($data->conversationId !== null) {
            $conversation = $this->userCommunicationRepository->findConversationByIdAndUserId(
                $data->conversationId,
                $userId
            );

            if ($conversation && $conversation->status !== ConversationStatus::CLOSED) {
                $conversation = $this->userCommunicationRepository->updateConversationStatus(
                    $conversation,
                    ConversationStatus::CLOSED
                );
                $closed[] = [
                    'conversation_id' => $conversation->id,
                    'status' => $conversation->status->value,
                ];
            }
        } else {
            $conversations = $this->userCommunicationRepository->findNonClosedConversationsByUserId($userId);

            foreach ($conversations as $conversation) {
                $conversation = $this->userCommunicationRepository->updateConversationStatus(
                    $conversation,
                    ConversationStatus::CLOSED
                );
                $closed[] = [
                    'conversation_id' => $conversation->id,
                    'status' => $conversation->status->value,
                ];
            }
        }

        return new CloseUserCommunicationResultData(closed: $closed);
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
            }

            return $conversation;
        }

        $latestConversation = $this->userCommunicationRepository->findLatestConversationByUserId($userId);
        if (! $latestConversation) {
            return $this->createConversationOrFail($userId, $conversationStatus);
        }

        if ($latestConversation->status !== $conversationStatus) {
            $latestConversation = $this->userCommunicationRepository->updateConversationStatus(
                $latestConversation,
                $conversationStatus
            );
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
            $conversation = $this->createConversationOrFail($userId, $targetStatus);
        } else {
            if ($targetStatus === ConversationStatus::WAITING_HUMAN) {
                $this->userCommunicationRepository->touchConversationLastAssignRequest($conversation);
                $conversation = $conversation->refresh();
            }
            $conversation = $this->userCommunicationRepository->updateConversationStatus($conversation, $targetStatus);
        }

        return $conversation;
    }

    /**
     * @throws ApiDomainException
     */
    private function createConversationOrFail(int $userId, ConversationStatus $status): Conversation
    {
        try {
            return $this->userCommunicationRepository->createConversation($userId, $status);
        } catch (Throwable $e) {
            Log::error('conversation.create_failed', [
                'user_id' => $userId,
                'status' => $status->value,
                'error' => $e->getMessage(),
            ]);
            throw new ApiDomainException(
                ApiDomainErrorCode::INTERNAL_SERVER_ERROR,
                'Failed to create conversation.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }
    }

}

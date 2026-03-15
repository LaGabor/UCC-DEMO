<?php

namespace App\Services;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\LargeLanguageMessageResponderServiceInterface;
use App\Contracts\Services\LLMServiceInterface;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Exceptions\ApiDomainException;
use Illuminate\Support\Facades\Log;
use Throwable;

class LLMService implements LLMServiceInterface
{
    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
        private readonly LargeLanguageMessageResponderServiceInterface $largeLanguageMessageResponderService,
    ) {
    }

    public function respondToUserMessageWithLlm(int $conversationId, int $userMessageId): void
    {
        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);

        if (! $conversation) {
            Log::warning('communication.llm_response.conversation_not_found', [
                'conversation_id' => $conversationId,
                'user_message_id' => $userMessageId,
            ]);

            return;
        }

        if ($conversation->status !== ConversationStatus::OPEN) {
            return;
        }

        $userMessage = $this->userCommunicationRepository->findMessageByIdAndConversationId(
            $userMessageId,
            $conversationId
        );
        if (! $userMessage) {
            Log::warning('communication.llm_response.message_not_found', [
                'conversation_id' => $conversationId,
                'user_message_id' => $userMessageId,
            ]);

            return;
        }

        $resolvedMessage = $this->largeLanguageMessageResponderService->resolve($conversation, $userMessage);

        if ($resolvedMessage === null) {
            $this->createMessageOrFail(
                $conversation->id,
                ConversationMessageSenderType::SYSTEM,
                ConversationMessageType::SYSTEM_ERROR,
                null,
                'System failed to generate response.'
            );

            $conversation->last_message_at = now();
            $conversation->save();

            return;
        }

        $this->createMessageOrFail(
            $conversation->id,
            ConversationMessageSenderType::BOT,
            ConversationMessageType::BOT_ANSWER,
            null,
            $resolvedMessage
        );

        $conversation->last_message_at = now();
        $conversation->save();
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
    ): \App\Models\ConversationMessage
    {
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

<?php

namespace App\Services;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\LargeLanguageMessageResponderServiceInterface;
use App\Contracts\Services\MessageRouterServiceInterface;
use App\Contracts\Services\SystemMessageBotServiceInterface;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Enums\MessageResponseSetting;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\MessageStrength;
use App\Exceptions\ApiDomainException;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageRouterService implements MessageRouterServiceInterface
{
    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
        private readonly SystemMessageBotServiceInterface $systemMessageBotService,
        private readonly LargeLanguageMessageResponderServiceInterface $largeLanguageMessageResponderService,
    ) {
    }

    public function routeUserMessage(int $conversationId, int $userMessageId): void
    {
        $conversation = $this->userCommunicationRepository->findConversationById($conversationId);

        if (! $conversation) {
            Log::warning('communication.route.conversation_not_found', [
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
            Log::warning('communication.route.message_not_found', [
                'conversation_id' => $conversationId,
                'user_message_id' => $userMessageId,
            ]);

            return;
        }

        $setting = MessageResponseSetting::tryFrom(
            (string) config('communication.message_response_setting', MessageResponseSetting::HYBRID->value)
        ) ?? MessageResponseSetting::HYBRID;

        $resolvedMessage = match ($setting) {
            MessageResponseSetting::SYSTEM_BOT => $this->resolveFromSystemBot($conversation, $userMessage),
            MessageResponseSetting::LLM => $this->resolveFromLlm($conversation, $userMessage),
            MessageResponseSetting::HYBRID => $this->resolveHybrid($conversation, $userMessage),
        };

        if ($resolvedMessage === null) {
            $errorMessage = $this->createMessageOrFail(
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

        $botMessage = $this->createMessageOrFail(
            $conversation->id,
            ConversationMessageSenderType::BOT,
            ConversationMessageType::BOT_ANSWER,
            null,
            $resolvedMessage
        );

        $conversation->last_message_at = now();
        $conversation->save();
    }

    private function resolveHybrid(Conversation $conversation, ConversationMessage $userMessage): ?string
    {
        $systemDecision = $this->systemMessageBotService->resolve($conversation, $userMessage);
        if (
            $systemDecision->strength->value > MessageStrength::VERY_WEAK->value
            && $systemDecision->message !== null
        ) {
            return $systemDecision->message;
        }

        return $this->largeLanguageMessageResponderService->resolve($conversation, $userMessage);
    }

    private function resolveFromSystemBot(Conversation $conversation, ConversationMessage $userMessage): ?string
    {
        $systemDecision = $this->systemMessageBotService->resolve($conversation, $userMessage);
        if ($systemDecision->strength === MessageStrength::ERROR) {
            return null;
        }

        return $systemDecision->message;
    }

    private function resolveFromLlm(Conversation $conversation, ConversationMessage $userMessage): ?string
    {
        return $this->largeLanguageMessageResponderService->resolve($conversation, $userMessage);
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

<?php

namespace App\Actions;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\SystemMessageBotServiceInterface;
use App\Data\Communication\UserMessageAcceptedData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Enums\MessageResponseSetting;
use App\Enums\MessageStrength;
use App\Exceptions\ApiDomainException;
use App\Jobs\LLMMessageJob;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class BotAnswerRouter
{
    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
        private readonly SystemMessageBotServiceInterface $systemMessageBotService,
    ) {
    }

    /**
     * @throws ApiDomainException
     */
    public function __invoke(Conversation $conversation, ConversationMessage $userMessage): UserMessageAcceptedData
    {
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
                $this->createMessageOrFail(
                    $conversation->id,
                    ConversationMessageSenderType::BOT,
                    ConversationMessageType::BOT_ANSWER,
                    null,
                    $systemDecision->message
                );
                $this->userCommunicationRepository->touchConversationLastMessageAt(
                    $conversation,
                    CarbonImmutable::now()
                );

                return new UserMessageAcceptedData(
                    conversationId: $conversation->id,
                    status: $conversation->status
                );
            }

            if ($setting === MessageResponseSetting::SYSTEM_BOT) {
                $this->createMessageOrFail(
                    $conversation->id,
                    ConversationMessageSenderType::SYSTEM,
                    ConversationMessageType::SYSTEM_NOTICE,
                    null,
                    'No predefined answer for this question.'
                );
                $this->userCommunicationRepository->touchConversationLastMessageAt(
                    $conversation,
                    CarbonImmutable::now()
                );

                return new UserMessageAcceptedData(
                    conversationId: $conversation->id,
                    status: $conversation->status
                );
            }
        }

        LLMMessageJob::dispatch($conversation->id, $userMessage->id);

        return new UserMessageAcceptedData(
            conversationId: $conversation->id,
            status: $conversation->status
        );
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

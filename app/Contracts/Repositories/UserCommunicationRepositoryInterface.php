<?php

namespace App\Contracts\Repositories;

use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface UserCommunicationRepositoryInterface
{
    public function findTodayConversationByUserId(int $userId, CarbonInterface $start, CarbonInterface $end): ?Conversation;

    public function findLatestConversationByUserId(int $userId): ?Conversation;

    public function findConversationByIdAndUserId(int $conversationId, int $userId): ?Conversation;

    public function findConversationById(int $conversationId): ?Conversation;

    public function findConversationsAssignedToAgent(int $agentId): Collection;

    public function createConversation(int $userId, ConversationStatus $status): Conversation;

    public function updateConversationStatus(Conversation $conversation, ConversationStatus $status): Conversation;

    public function touchConversationLastAssignRequest(Conversation $conversation): void;

    public function touchConversationLastMessageAt(Conversation $conversation, CarbonInterface $timestamp): void;

    public function listMessagesForConversation(int $conversationId, ?int $limit = null): Collection;

    public function getLatestMessagesForConversation(int $conversationId, int $limit = 10): Collection;

    public function createMessage(
        int $conversationId,
        ConversationMessageSenderType $senderType,
        ConversationMessageType $messageType,
        ?int $senderUserId,
        ?string $messageText
    ): ConversationMessage;

    public function findMessageByIdAndConversationId(int $messageId, int $conversationId): ?ConversationMessage;

    public function findOpenConversationsWithLastMessageBefore(CarbonInterface $before): Collection;

    public function findNonClosedStaleConversations(CarbonInterface $before): Collection;

    public function findNonClosedConversationsByUserId(int $userId): Collection;
}

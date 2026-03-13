<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use App\Enums\ConversationStatus;
use App\Exceptions\ApiDomainException;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Throwable;

class UserCommunicationRepository implements UserCommunicationRepositoryInterface
{
    public function findTodayConversationByUserId(int $userId, CarbonInterface $start, CarbonInterface $end): ?Conversation
    {
        return Conversation::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->first();
    }

    public function findLatestConversationByUserId(int $userId): ?Conversation
    {
        return Conversation::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();
    }

    public function findConversationByIdAndUserId(int $conversationId, int $userId): ?Conversation
    {
        return Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->first();
    }

    public function findConversationById(int $conversationId): ?Conversation
    {
        return Conversation::query()->find($conversationId);
    }

    public function createConversation(int $userId, ConversationStatus $status): Conversation
    {
        try {
            return Conversation::query()->create([
                'user_id' => $userId,
                'status' => $status,
                'last_message_at' => now(),
                'closed_at' => $status === ConversationStatus::CLOSED ? now() : null,
            ]);
        } catch (Throwable $exception) {
            Log::error('conversation.create_failed', [
                'user_id' => $userId,
                'status' => $status->value,
                'error' => $exception->getMessage(),
            ]);

            throw new ApiDomainException(
                ApiDomainErrorCode::INTERNAL_SERVER_ERROR,
                'Failed to create conversation.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }
    }

    public function updateConversationStatus(Conversation $conversation, ConversationStatus $status): Conversation
    {
        $conversation->status = $status;

        if ($status === ConversationStatus::CLOSED) {
            $conversation->closed_at = now();
            $conversation->last_closed_at = CarbonImmutable::now();
        } else {
            $conversation->closed_at = null;
        }

        if ($status === ConversationStatus::OPEN) {
            $conversation->last_open_at = CarbonImmutable::now();
        }

        if ($status === ConversationStatus::ASSIGNED) {
            $conversation->last_assigned_at = CarbonImmutable::now();
        }

        if ($status !== ConversationStatus::ASSIGNED) {
            $conversation->assigned_agent_id = null;
        }

        $conversation->save();

        return $conversation->refresh();
    }

    public function touchConversationLastAssignedCallIfNull(Conversation $conversation): void
    {
        if ($conversation->last_assigned_call !== null) {
            return;
        }

        $conversation->last_assigned_call = CarbonImmutable::now();
        $conversation->save();
    }

    public function touchConversationLastMessageAt(Conversation $conversation, CarbonInterface $timestamp): void
    {
        $conversation->last_message_at = $timestamp;
        $conversation->save();
    }

    public function listMessagesForConversation(int $conversationId, ?int $limit = null): Collection
    {
        if ($limit !== null) {
            return ConversationMessage::query()
                ->where('conversation_id', $conversationId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();
        }

        return ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function getLatestMessagesForConversation(int $conversationId, int $limit = 10): Collection
    {
        return ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function createMessage(
        int $conversationId,
        ConversationMessageSenderType $senderType,
        ConversationMessageType $messageType,
        ?int $senderUserId,
        ?string $messageText
    ): ConversationMessage {
        try {
            return ConversationMessage::query()->create([
                'conversation_id' => $conversationId,
                'sender_type' => $senderType,
                'sender_user_id' => $senderUserId,
                'message_type' => $messageType,
                'message_text' => $messageText,
            ]);
        } catch (Throwable $exception) {
            Log::error('conversation.message_create_failed', [
                'conversation_id' => $conversationId,
                'sender_type' => $senderType->value,
                'message_type' => $messageType->value,
                'error' => $exception->getMessage(),
            ]);

            throw new ApiDomainException(
                ApiDomainErrorCode::INTERNAL_SERVER_ERROR,
                'Failed to create conversation message.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }
    }

    public function findMessageByIdAndConversationId(int $messageId, int $conversationId): ?ConversationMessage
    {
        return ConversationMessage::query()
            ->where('id', $messageId)
            ->where('conversation_id', $conversationId)
            ->first();
    }

    public function findOpenConversationsWithLastMessageBefore(CarbonInterface $before): Collection
    {
        return Conversation::query()
            ->where('status', ConversationStatus::OPEN)
            ->where('last_message_at', '<', $before)
            ->get();
    }
}

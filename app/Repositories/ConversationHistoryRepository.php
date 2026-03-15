<?php

namespace App\Repositories;

use App\Contracts\Repositories\ConversationHistoryRepositoryInterface;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Data\ConversationHistory\ConversationHistoryListEntryData;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Support\Collection;

class ConversationHistoryRepository implements ConversationHistoryRepositoryInterface
{
    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
    ) {
    }

    public function getAllForList(): Collection
    {
        $conversations = Conversation::query()
            ->with('user')
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->get();

        if ($conversations->isEmpty()) {
            return collect();
        }

        $conversationIds = $conversations->pluck('id')->all();
        $latestMessages = ConversationMessage::query()
            ->whereIn('conversation_id', $conversationIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('conversation_id')
            ->map(fn (Collection $group) => $group->first());

        return $conversations->map(function (Conversation $conversation) use ($latestMessages) {
            $latest = $latestMessages->get($conversation->id);
            $user = $conversation->user;

            return new ConversationHistoryListEntryData(
                conversation_id: $conversation->id,
                user_id: $conversation->user_id,
                user_name: $user?->name ?? '',
                last_message_at: $conversation->last_message_at?->toIso8601String(),
                last_message_text: $latest?->message_text,
            );
        });
    }

    public function getFullMessageHistory(int $conversationId): Collection
    {
        return $this->userCommunicationRepository->listMessagesForConversation($conversationId, null);
    }
}

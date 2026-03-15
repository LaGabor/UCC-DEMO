<?php

namespace App\Jobs;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Services\LLMServiceInterface;
use App\Enums\ConversationMessageSenderType;
use App\Enums\ConversationMessageType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RouteConversationMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 3;

    public function __construct(
        public readonly int $conversationId,
        public readonly int $userMessageId,
    ) {
        $this->onQueue('bot-messages');
    }

    public function handle(LLMServiceInterface $messageRouterService): void
    {
        $messageRouterService->routeUserMessage($this->conversationId, $this->userMessageId);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('communication.route.job_failed', [
            'conversation_id' => $this->conversationId,
            'user_message_id' => $this->userMessageId,
            'error' => $exception?->getMessage(),
        ]);

        try {
            $repository = app(UserCommunicationRepositoryInterface::class);
            $conversation = $repository->findConversationById($this->conversationId);
            if (! $conversation) {
                return;
            }

            $errorMessage = $repository->createMessage(
                $this->conversationId,
                ConversationMessageSenderType::SYSTEM,
                ConversationMessageType::SYSTEM_ERROR,
                null,
                'System failed to generate response.'
            );

            $conversation->last_message_at = now();
            $conversation->save();
        } catch (\Throwable $e) {
            Log::warning('communication.route.failed_broadcast_error', [
                'conversation_id' => $this->conversationId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

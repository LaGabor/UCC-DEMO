<?php

namespace App\Observers;

use App\Events\ConversationMessageBroadcasted;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Log;

class ConversationMessageObserver
{
    public function created(ConversationMessage $message): void
    {
        try {
            $message->loadMissing('conversation');
            $conversation = $message->conversation?->refresh();

            if ($conversation) {
                ConversationMessageBroadcasted::dispatch($conversation, $message);
            }
        } catch (\Throwable $e) {
            Log::error('conversation_message.observer.broadcast_failed', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}

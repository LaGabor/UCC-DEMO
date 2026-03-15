<?php

namespace App\Observers;

use App\Events\AgentMonitorConversationBroadcasted;
use App\Events\ConversationStatusBroadcasted;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ConversationObserver
{
    public function created(Conversation $conversation): void
    {
        $this->dispatchBroadcasts($conversation);
    }

    public function updated(Conversation $conversation): void
    {
        if (! $conversation->wasChanged('status') && ! $conversation->wasChanged('assigned_agent_id')) {
            return;
        }

        $this->dispatchBroadcasts($conversation);
    }

    private function dispatchBroadcasts(Conversation $conversation): void
    {
        try {
            ConversationStatusBroadcasted::dispatch($conversation);
            AgentMonitorConversationBroadcasted::dispatch($conversation);
        } catch (\Throwable $e) {
            Log::error('conversation.observer.broadcast_failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}

<?php

namespace App\Contracts\Services;

use App\Data\Communication\MessageDecisionData;
use App\Models\Conversation;
use App\Models\ConversationMessage;

interface SystemMessageBotServiceInterface
{
    public function resolve(Conversation $conversation, ConversationMessage $userMessage): MessageDecisionData;
}

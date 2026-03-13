<?php

namespace App\Contracts\Services;

use App\Models\Conversation;
use App\Models\ConversationMessage;

interface LargeLanguageMessageResponderServiceInterface
{
    public function resolve(Conversation $conversation, ConversationMessage $userMessage): ?string;
}

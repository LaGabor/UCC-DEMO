<?php

namespace App\Contracts\Services;

interface LLMServiceInterface
{
    public function respondToUserMessageWithLlm(int $conversationId, int $userMessageId): void;
}

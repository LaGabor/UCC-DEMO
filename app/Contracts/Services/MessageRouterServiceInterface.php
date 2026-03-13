<?php

namespace App\Contracts\Services;

interface MessageRouterServiceInterface
{
    public function routeUserMessage(int $conversationId, int $userMessageId): void;
}

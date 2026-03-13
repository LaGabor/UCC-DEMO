<?php

namespace App\Contracts\Services;

use App\Data\Communication\UpdateConversationStatusData;
use App\Data\Communication\UserMessageInputData;
use App\Data\Communication\UserMessageAcceptedData;
use App\Data\Communication\UserConversationResponseData;

interface UserCommunicationServiceInterface
{
    public function getUserConversation(int $userId): UserConversationResponseData;

    public function sendUserMessage(int $userId, UserMessageInputData $data): UserMessageAcceptedData;

    public function callAgent(int $userId, UpdateConversationStatusData $data): UserConversationResponseData;

    public function cancelCall(int $userId, UpdateConversationStatusData $data): UserConversationResponseData;
}

<?php

namespace App\Contracts\Repositories;

use App\Data\ConversationHistory\ConversationHistoryListEntryData;
use Illuminate\Support\Collection;

interface ConversationHistoryRepositoryInterface
{
    public function getAllForList(): Collection;

    public function getFullMessageHistory(int $conversationId): Collection;
}

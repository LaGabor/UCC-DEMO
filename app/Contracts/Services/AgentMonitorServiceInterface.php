<?php

namespace App\Contracts\Services;

use Illuminate\Support\Collection;

interface AgentMonitorServiceInterface
{
    public function getNonClosedConversations(int $requestingUserId): Collection;
}

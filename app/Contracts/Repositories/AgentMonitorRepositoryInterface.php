<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;

interface AgentMonitorRepositoryInterface
{
    /**
     * @return Collection<int, \App\Data\AgentMonitor\AgentMonitorConversationData>
     */
    public function getNonClosedConversations(): Collection;
}

<?php

namespace App\Services;

use App\Contracts\Repositories\AgentMonitorRepositoryInterface;
use App\Contracts\Services\AgentMonitorServiceInterface;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiDomainException;
use App\Models\User;
use Illuminate\Support\Collection;

class AgentMonitorService implements AgentMonitorServiceInterface
{
    public function __construct(
        private readonly AgentMonitorRepositoryInterface $agentMonitorRepository,
    ) {
    }

    public function getNonClosedConversations(int $requestingUserId): Collection
    {
        $user = User::query()->find($requestingUserId);
        if (! $user) {
            throw new ApiDomainException(
                ApiDomainErrorCode::USER_NOT_FOUND,
                'User not found.',
                null,
                ApiDomainStatus::UNAUTHORIZED
            );
        }

        if (! in_array($user->role, [UserRole::ADMIN, UserRole::HELPDESK_AGENT], true)) {
            throw new ApiDomainException(
                ApiDomainErrorCode::AGENT_MONITOR_ACCESS_DENIED,
                'Only admin or helpdesk agent can access agent monitor.',
                null,
                ApiDomainStatus::FORBIDDEN
            );
        }

        return $this->agentMonitorRepository->getNonClosedConversations();
    }
}

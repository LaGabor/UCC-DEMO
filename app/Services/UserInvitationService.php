<?php

namespace App\Services;

use App\Contracts\Services\UserInvitationServiceInterface;
use App\Data\Admin\CreateUserInvitationData;
use App\Data\Public\AcceptUserInvitationData;
use App\Repositories\UserInvitation\UserInvitationRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;

class UserInvitationService implements UserInvitationServiceInterface
{
    public function __construct(
        private readonly UserInvitationRepositoryInterface $userInvitationRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function create(CreateUserInvitationData $data): void
    {
        $this->userInvitationRepository->createInvitationForPendingUser($data);
    }

    public function getInvitationByToken(string $token): array
    {
        return $this->userInvitationRepository->getInvitationPayloadByToken($token);
    }

    public function accept(AcceptUserInvitationData $data): void
    {
        $this->userInvitationRepository->acceptInvitation($data);
    }
}

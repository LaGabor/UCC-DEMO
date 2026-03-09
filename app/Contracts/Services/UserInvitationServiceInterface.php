<?php

namespace App\Contracts\Services;

use App\Data\Admin\CreateUserInvitationData;
use App\Data\Public\AcceptUserInvitationData;

interface UserInvitationServiceInterface
{
    public function create(CreateUserInvitationData $data): void;

    public function getInvitationByToken(string $token): array;

    public function accept(AcceptUserInvitationData $data): void;
}

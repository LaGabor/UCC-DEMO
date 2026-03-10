<?php

namespace App\Contracts\Services;

use App\Data\Admin\CreateUserInvitationData;
use App\Data\Public\AcceptUserInvitationData;
use App\Data\Public\UserInvitationPayloadData;

interface UserInvitationServiceInterface
{
    public function create(CreateUserInvitationData $data): void;

    public function getInvitationByToken(string $token): UserInvitationPayloadData;

    public function accept(AcceptUserInvitationData $data): void;
}

<?php

namespace App\Contracts\Repositories;

interface UserInvitationRepositoryInterface
{
    public function createInvitationTokenForEmail(string $email): string;

    public function findInvitationTokenByToken(string $token): ?object;

    public function deleteInvitationToken(string $token): void;
}

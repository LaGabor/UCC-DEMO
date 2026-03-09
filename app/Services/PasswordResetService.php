<?php

namespace App\Services;

use App\Data\Public\CreatePasswordResetRequestData;
use App\Data\Public\CompletePasswordResetData;
use App\Repositories\PasswordReset\PasswordResetRepositoryInterface;

class PasswordResetService implements PasswordResetServiceInterface
{
    public function __construct(
        private readonly PasswordResetRepositoryInterface $passwordResetRepository,
    ) {
    }

    public function create(CreatePasswordResetRequestData $data): void
    {
        $this->passwordResetRepository->createPasswordResetRequest($data);
    }

    public function getResetRequestByToken(string $token): array
    {
        return $this->passwordResetRepository->getPasswordResetPayloadByToken($token);
    }

    public function complete(CompletePasswordResetData $data): void
    {
        $this->passwordResetRepository->completePasswordReset($data);
    }
}

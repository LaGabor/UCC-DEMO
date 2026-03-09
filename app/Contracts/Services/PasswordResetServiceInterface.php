<?php

namespace App\Contracts\Services;

use App\Data\Public\CreatePasswordResetRequestData;
use App\Data\Public\CompletePasswordResetData;

interface PasswordResetServiceInterface
{
    public function create(CreatePasswordResetRequestData $data): void;

    public function getResetRequestByToken(string $token): array;

    public function complete(CompletePasswordResetData $data): void;
}

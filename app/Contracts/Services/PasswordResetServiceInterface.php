<?php

namespace App\Contracts\Services;

use App\Data\Public\CreatePasswordResetRequestData;
use App\Data\Public\CompletePasswordResetData;
use App\Data\Public\PasswordResetPayloadData;

interface PasswordResetServiceInterface
{
    public function create(CreatePasswordResetRequestData $data): void;

    public function getResetRequestByToken(string $token): PasswordResetPayloadData;

    public function complete(CompletePasswordResetData $data): void;
}

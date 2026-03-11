<?php

namespace App\Contracts\Services;

use App\Data\User\UpdatePreferredLocaleData;

interface UserServiceInterface
{
    public function updatePreferredLocale(int $userId, UpdatePreferredLocaleData $data): void;
}


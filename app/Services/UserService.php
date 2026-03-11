<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Data\User\UpdatePreferredLocaleData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Exceptions\ApiDomainException;

class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function updatePreferredLocale(int $userId, UpdatePreferredLocaleData $data): void
    {
        $updatedRows = $this->userRepository->updatePreferredLocaleByUserId(
            $userId,
            $data->preferredLocale
        );

        if ($updatedRows === 0) {
            throw new ApiDomainException(
                ApiDomainErrorCode::USER_NOT_FOUND,
                'No user found for preferred locale update.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }
    }
}


<?php

namespace App\Services;

use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\PasswordResetServiceInterface;
use App\Data\Public\CreatePasswordResetRequestData;
use App\Data\Public\CompletePasswordResetData;
use App\Data\Public\PasswordResetPayloadData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Exceptions\ApiDomainException;
use App\Jobs\SendPasswordResetEmailJob;
use Illuminate\Support\Facades\DB;

class PasswordResetService implements PasswordResetServiceInterface
{
    private const VALID_FOR_DAYS = 5;

    public function __construct(
        private readonly PasswordResetRepositoryInterface $passwordResetRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function create(CreatePasswordResetRequestData $data): void
    {
        if (! $this->userRepository->existsActiveByEmail($data->email)) {
            return;
        }

        $activeTokenExists = $this->passwordResetRepository->hasActivePasswordResetToken(
            $data->email,
            now()->subDays(self::VALID_FOR_DAYS)
        );

        if ($activeTokenExists) {
            return;
        }

        $token = $this->passwordResetRepository
            ->createPasswordResetTokenForEmail($data->email);

        if (! $token) {
            return;
        }

        SendPasswordResetEmailJob::dispatch($data->email, $token)
            ->afterResponse();
    }

    public function getResetRequestByToken(string $token): PasswordResetPayloadData
    {
        $record = $this->passwordResetRepository->findPasswordResetTokenByToken($token);

        if (! $record || ! $record->created_at) {
            throw new ApiDomainException(
                ApiDomainErrorCode::PASSWORD_RESET_TOKEN_INVALID,
                'Password reset token is invalid.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $payload = PasswordResetPayloadData::fromTokenRecord(
            $record,
            self::VALID_FOR_DAYS
        );

        if ($payload->isExpired()) {
            throw new ApiDomainException(
                ApiDomainErrorCode::PASSWORD_RESET_TOKEN_EXPIRED,
                'Password reset token has expired.',
                null,
                ApiDomainStatus::UNPROCESSABLE_ENTITY
            );
        }

        return $payload;
    }

    public function complete(CompletePasswordResetData $data): void
    {
        DB::transaction(function () use ($data): void {
            $record = $this->passwordResetRepository
                ->findPasswordResetTokenByToken($data->token);

            if (! $record || ! $record->created_at) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::PASSWORD_RESET_TOKEN_INVALID,
                    'Password reset token is invalid.',
                    null,
                    ApiDomainStatus::NOT_FOUND
                );
            }

            $payload = PasswordResetPayloadData::fromTokenRecord(
                $record,
                self::VALID_FOR_DAYS
            );

            if ($payload->isExpired()) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::PASSWORD_RESET_TOKEN_EXPIRED,
                    'Password reset token has expired.',
                    null,
                    ApiDomainStatus::UNPROCESSABLE_ENTITY
                );
            }

            $updatedRows = $this->userRepository->updatePasswordByEmail(
                $record->email,
                $data->password
            );

            if ($updatedRows === 0) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::USER_NOT_FOUND,
                    'No user found for the given email address.',
                    null,
                    ApiDomainStatus::NOT_FOUND
                );
            }

            $this->passwordResetRepository->deletePasswordResetToken($data->token);
        });
    }
}

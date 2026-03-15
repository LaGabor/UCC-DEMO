<?php

namespace App\Services;

use App\Contracts\Services\UserInvitationServiceInterface;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Data\Admin\CreateUserInvitationData;
use App\Data\Public\AcceptUserInvitationData;
use App\Data\Public\UserInvitationPayloadData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Enums\ConversationStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiDomainException;
use App\Jobs\SendInvitationEmailJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserInvitationService implements UserInvitationServiceInterface
{
    private const VALID_FOR_DAYS = 5;

    public function __construct(
        private readonly UserInvitationRepositoryInterface $userInvitationRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
    ) {
    }

    public function create(CreateUserInvitationData $data): void
    {
        if ($this->userRepository->existsByEmail($data->email)) {
            throw new ApiDomainException(
                ApiDomainErrorCode::EMAIL_ALREADY_EXISTS,
                'A user with this email already exists.',
                ['email' => ['This email is already in use.']],
                ApiDomainStatus::UNPROCESSABLE_ENTITY
            );
        }

        $token = DB::transaction(function () use ($data): string {
            $hashedPendingPassword = Hash::make($this->resolvePendingUserPassword());

            $this->userRepository->createPendingUser(
                $data->email,
                $data->role,
                $hashedPendingPassword
            );

            return $this->userInvitationRepository
                ->createInvitationTokenForEmail($data->email);
        });

        SendInvitationEmailJob::dispatch($data->email, $token)
            ->afterResponse();
    }

    public function getInvitationByToken(string $token): UserInvitationPayloadData
    {
        $record = $this->userInvitationRepository->findInvitationTokenByToken($token);

        if (! $record || ! $record->created_at) {
            throw new ApiDomainException(
                ApiDomainErrorCode::INVITATION_TOKEN_INVALID,
                'Invitation token is invalid.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        $payload = UserInvitationPayloadData::fromTokenRecord(
            $record,
            self::VALID_FOR_DAYS
        );

        if ($payload->isExpired()) {
            throw new ApiDomainException(
                ApiDomainErrorCode::INVITATION_TOKEN_EXPIRED,
                'Invitation token has expired.',
                null,
                ApiDomainStatus::UNPROCESSABLE_ENTITY
            );
        }

        return $payload;
    }

    public function accept(AcceptUserInvitationData $data): void
    {
        DB::transaction(function () use ($data): void {
            $record = $this->userInvitationRepository
                ->findInvitationTokenByToken($data->token);

            if (! $record || ! $record->created_at) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::INVITATION_TOKEN_INVALID,
                    'Invitation token is invalid.',
                    null,
                    ApiDomainStatus::NOT_FOUND
                );
            }

            $payload = UserInvitationPayloadData::fromTokenRecord(
                $record,
                self::VALID_FOR_DAYS
            );

            if ($payload->isExpired()) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::INVITATION_TOKEN_EXPIRED,
                    'Invitation token has expired.',
                    null,
                    ApiDomainStatus::UNPROCESSABLE_ENTITY
                );
            }

            $updatedRows = $this->userRepository->activateInvitedUser(
                $record->email,
                $data->name,
                $data->password,
                $data->preferredLocale
            );

            if ($updatedRows === 0) {
                throw new ApiDomainException(
                    ApiDomainErrorCode::INVITATION_USER_NOT_PENDING,
                    'Invitation cannot be accepted for this user.',
                    null,
                    ApiDomainStatus::UNPROCESSABLE_ENTITY
                );
            }

            $userId = $this->userRepository->findIdByEmail($record->email);
            if ($userId !== null) {
                $user = $this->userRepository->findById($userId);
                if ($user && $user->role === UserRole::USER) {
                    $this->createConversationOrFail($userId, ConversationStatus::CLOSED);
                }
            }

            $this->userInvitationRepository->deleteInvitationToken($data->token);
        });
    }

    private function resolvePendingUserPassword(): string
    {
        $configuredPassword = trim((string) config('auth.pending_user_initial_password'));

        if ($configuredPassword === '') {
            throw new ApiDomainException(
                ApiDomainErrorCode::PENDING_USER_INITIAL_PASSWORD_MISSING,
                'Pending user initial password is not configured.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }

        return $configuredPassword;
    }

    private function createConversationOrFail(int $userId, ConversationStatus $status): void
    {
        try {
            $this->userCommunicationRepository->createConversation($userId, $status);
        } catch (Throwable $e) {
            Log::error('conversation.create_failed', [
                'user_id' => $userId,
                'status' => $status->value,
                'error' => $e->getMessage(),
            ]);
            throw new ApiDomainException(
                ApiDomainErrorCode::INTERNAL_SERVER_ERROR,
                'Failed to create conversation.',
                null,
                ApiDomainStatus::INTERNAL_SERVER_ERROR
            );
        }
    }
}

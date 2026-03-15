<?php

namespace App\Contracts\Repositories;

use App\Enums\Language;
use App\Enums\UserRole;
use App\Models\User;

interface UserRepositoryInterface
{
    public function existsByEmail(string $email): bool;

    public function existsActiveByEmail(string $email): bool;

    public function findIdByEmail(string $email): ?int;

    public function findById(int $id): ?User;

    public function createPendingUser(
        string $email,
        UserRole $role,
        string $hashedPassword
    ): void;

    public function activateInvitedUser(
        string $email,
        string $name,
        string $password,
        Language $preferredLocale
    ): int;

    public function updatePasswordByEmail(string $email, string $password): int;

    public function updatePreferredLocaleByUserId(int $userId, Language $preferredLocale): int;
}

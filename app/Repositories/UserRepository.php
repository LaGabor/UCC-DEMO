<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\Language;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function existsByEmail(string $email): bool
    {
        return User::query()
            ->where('email', $email)
            ->exists();
    }

    public function existsActiveByEmail(string $email): bool
    {
        return User::query()
            ->where('email', $email)
            ->where('status', UserStatus::ACTIVE)
            ->exists();
    }

    public function createPendingUser(
        string $email,
        UserRole $role,
        string $hashedPassword
    ): void
    {
        User::query()->create([
            'name' => str($email)->before('@')->toString(),
            'email' => $email,
            'role' => $role,
            'status' => UserStatus::PENDING,
            'preferred_locale' => Language::EN,
            'password' => $hashedPassword,
        ]);
    }

    public function activateInvitedUser(
        string $email,
        string $name,
        string $password,
        Language $preferredLocale
    ): int {
        return User::query()
            ->where('email', $email)
            ->where('status', UserStatus::PENDING)
            ->update([
                'name' => $name,
                'password' => Hash::make($password),
                'preferred_locale' => $preferredLocale,
                'status' => UserStatus::ACTIVE,
            ]);
    }

    public function updatePasswordByEmail(string $email, string $password): int
    {
        return User::query()
            ->where('email', $email)
            ->update([
                'password' => Hash::make($password),
            ]);
    }

    public function updatePreferredLocaleByUserId(int $userId, Language $preferredLocale): int
    {
        return User::query()
            ->where('id', $userId)
            ->update([
                'preferred_locale' => $preferredLocale,
            ]);
    }
}

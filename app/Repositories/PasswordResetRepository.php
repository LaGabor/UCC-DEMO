<?php

namespace App\Repositories;

use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Enums\PasswordResetTokenType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetRepository implements PasswordResetRepositoryInterface
{
    private const TOKEN_TYPE = PasswordResetTokenType::PASSWORD_RESET;

    public function hasActivePasswordResetToken(string $email, \DateTimeInterface $notOlderThan): bool
    {
        return DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('type', self::TOKEN_TYPE->value)
            ->where('created_at', '>=', $notOlderThan)
            ->exists();
    }

    public function createPasswordResetTokenForEmail(string $email): string
    {
        $token = hash('sha256', Str::random(64));

        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'email' => $email,
                'type' => self::TOKEN_TYPE->value,
            ],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        return $token;
    }

    public function findPasswordResetTokenByToken(string $token): ?object
    {
        return DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('type', self::TOKEN_TYPE->value)
            ->first();
    }

    public function deletePasswordResetToken(string $token): void
    {
        DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('type', self::TOKEN_TYPE->value)
            ->delete();
    }
}

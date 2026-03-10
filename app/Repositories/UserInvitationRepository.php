<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Enums\PasswordResetTokenType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserInvitationRepository implements UserInvitationRepositoryInterface
{
    private const TOKEN_TYPE = PasswordResetTokenType::INVITATION;

    public function createInvitationTokenForEmail(string $email): string
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

    public function findInvitationTokenByToken(string $token): ?object
    {
        return DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('type', self::TOKEN_TYPE->value)
            ->first();
    }

    public function deleteInvitationToken(string $token): void
    {
        DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('type', self::TOKEN_TYPE->value)
            ->delete();
    }
}

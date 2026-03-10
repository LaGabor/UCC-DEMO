<?php

namespace App\Contracts\Repositories;

interface PasswordResetRepositoryInterface
{
    public function hasActivePasswordResetToken(string $email, \DateTimeInterface $notOlderThan): bool;

    public function createPasswordResetTokenForEmail(string $email): string;

    public function findPasswordResetTokenByToken(string $token): ?object;

    public function deletePasswordResetToken(string $token): void;
}

<?php

namespace App\Data\Public;

use Carbon\CarbonImmutable;

class UserInvitationPayloadData
{
    public function __construct(
        public readonly string $email,
        public readonly string $expiresAt,
    ) {
    }

    public static function fromTokenRecord(object $record, int $validForDays): self
    {
        $createdAt = CarbonImmutable::parse($record->created_at);

        return new self(
            email: $record->email,
            expiresAt: $createdAt->addDays($validForDays)->toIso8601String(),
        );
    }

    /**
     * @return array{email:string,expires_at:string}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'expires_at' => $this->expiresAt,
        ];
    }

    public function isExpired(): bool
    {
        return now()->gt(CarbonImmutable::parse($this->expiresAt));
    }
}

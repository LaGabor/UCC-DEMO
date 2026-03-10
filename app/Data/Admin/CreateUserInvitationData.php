<?php

namespace App\Data\Admin;

use App\Enums\UserRole;
use App\Http\Requests\API\Admin\StoreUserInvitationRequest;

class CreateUserInvitationData
{
    public function __construct(
        public readonly string $email,
        public readonly UserRole $role,
        public readonly int $createdByUserId,
    ) {
    }

    public static function fromRequest(StoreUserInvitationRequest $request): self
    {
        return new self(
            email: mb_strtolower($request->string('email')->toString()),
            role: UserRole::from($request->string('role')->toString()),
            createdByUserId: (int) $request->user()->id,
        );
    }
}

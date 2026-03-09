<?php

namespace App\Data\Admin;

use App\Http\Requests\Api\Admin\StoreUserInvitationRequest;

class CreateUserInvitationData
{
    public function __construct(
        public readonly string $email,
        public readonly int $createdByUserId,
    ) {
    }

    public static function fromRequest(StoreUserInvitationRequest $request): self
    {
        return new self(
            email: mb_strtolower($request->string('email')->toString()),
            createdByUserId: (int) $request->user()->id,
        );
    }
}

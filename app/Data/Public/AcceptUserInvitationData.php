<?php

namespace App\Data\Public;

use App\Http\Requests\Api\Public\AcceptUserInvitationRequest;

class AcceptUserInvitationData
{
    public function __construct(
        public readonly string $token,
        public readonly string $name,
        public readonly string $password,
        public readonly string $preferredLocale,
    ) {
    }

    public static function fromRequest(
        AcceptUserInvitationRequest $request,
        string $token
    ): self {
        return new self(
            token: $token,
            name: $request->string('name')->toString(),
            password: $request->string('password')->toString(),
            preferredLocale: $request->string('preferred_locale')->toString(),
        );
    }
}

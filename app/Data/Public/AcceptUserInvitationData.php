<?php

namespace App\Data\Public;

use App\Enums\Language;
use App\Http\Requests\API\Public\AcceptUserInvitationRequest;

class AcceptUserInvitationData
{
    public function __construct(
        public readonly string $token,
        public readonly string $name,
        public readonly string $password,
        public readonly Language $preferredLocale,
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
            preferredLocale: Language::from($request->string('preferred_locale')->toString()),
        );
    }
}

<?php

namespace App\Data\Public;

use App\Http\Requests\Api\Public\CompletePasswordResetRequest;

class CompletePasswordResetData
{
    public function __construct(
        public readonly string $token,
        public readonly string $password,
    ) {
    }

    public static function fromRequest(
        CompletePasswordResetRequest $request,
        string $token
    ): self {
        return new self(
            token: $token,
            password: $request->string('password')->toString(),
        );
    }
}

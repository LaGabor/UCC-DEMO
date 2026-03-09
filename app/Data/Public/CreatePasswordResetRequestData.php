<?php

namespace App\Data\Public;

use App\Http\Requests\Api\Public\StorePasswordResetRequest;

class CreatePasswordResetRequestData
{
    public function __construct(
        public readonly string $email,
    ) {
    }

    public static function fromRequest(StorePasswordResetRequest $request): self
    {
        return new self(
            email: mb_strtolower($request->string('email')->toString()),
        );
    }
}

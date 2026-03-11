<?php

namespace App\Data\User;

use App\Enums\Language;
use App\Http\Requests\API\User\UpdatePreferredLocaleRequest;

class UpdatePreferredLocaleData
{
    public function __construct(
        public readonly Language $preferredLocale,
    ) {
    }

    public static function fromRequest(UpdatePreferredLocaleRequest $request): self
    {
        return new self(
            preferredLocale: Language::from($request->string('preferred_locale')->toString()),
        );
    }
}


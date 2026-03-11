<?php

namespace App\Data\Events;

use App\Http\Requests\API\Events\UpdateEventRequest;

class UpdateEventDescriptionData
{
    public function __construct(
        public readonly ?string $description,
    ) {
    }

    public static function fromRequest(UpdateEventRequest $request): self
    {
        $description = $request->input('description');

        return new self(
            description: is_string($description) && $description !== '' ? $description : null,
        );
    }
}


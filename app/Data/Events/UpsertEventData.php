<?php

namespace App\Data\Events;

use App\Http\Requests\API\Events\StoreEventRequest;
use App\Http\Requests\API\Events\UpdateEventRequest;

class UpsertEventData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $occursAt,
    ) {
    }

    public static function fromStoreRequest(StoreEventRequest $request): self
    {
        return self::fromValidatedValues(
            title: $request->string('title')->toString(),
            description: $request->input('description'),
            occursAt: $request->date('occurs_at')?->toIso8601String() ?? ''
        );
    }

    public static function fromUpdateRequest(UpdateEventRequest $request): self
    {
        return self::fromValidatedValues(
            title: $request->string('title')->toString(),
            description: $request->input('description'),
            occursAt: $request->date('occurs_at')?->toIso8601String() ?? ''
        );
    }

    private static function fromValidatedValues(
        string $title,
        mixed $description,
        string $occursAt
    ): self {
        return new self(
            title: $title,
            description: is_string($description) && $description !== '' ? $description : null,
            occursAt: $occursAt
        );
    }
}

